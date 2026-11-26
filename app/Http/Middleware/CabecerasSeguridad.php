<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras HTTP de seguridad (ISO 25010 – Seguridad / Resistencia).
 *
 *  - Content-Security-Policy: solo se cargan scripts, estilos, fuentes e
 *    imágenes del propio sistema y de los CDN que usan las vistas; ningún
 *    sitio externo puede mostrar SIGIP dentro de un iframe ni recibir sus formularios.
 *  - X-Frame-Options / X-Content-Type-Options / Referrer-Policy / Permissions-Policy.
 *  - Strict-Transport-Security cuando la conexión es HTTPS.
 *
 * Pendiente: las vistas aún tienen scripts y atributos onclick en línea, por eso
 * script-src incluye 'unsafe-inline'. Al moverlos a archivos .js se puede quitar.
 */
class CabecerasSeguridad
{
    private const CDN_ESTILOS = ['https://cdn.jsdelivr.net', 'https://fonts.googleapis.com', 'https://fonts.bunny.net'];

    private const CDN_FUENTES = ['https://fonts.gstatic.com', 'https://fonts.bunny.net', 'https://cdn.jsdelivr.net'];

    private const CDN_SCRIPTS = ['https://cdn.jsdelivr.net'];

    private const CDN_IMAGENES = ['https://img.icons8.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $cabeceras = [
            'Content-Security-Policy' => $this->politica(),
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if ($request->isSecure()) {
            $cabeceras['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($cabeceras as $nombre => $valor) {
            if (!$respuesta->headers->has($nombre)) {
                $respuesta->headers->set($nombre, $valor);
            }
        }

        // No revelar la versión de PHP.
        $respuesta->headers->remove('X-Powered-By');

        return $respuesta;
    }

    private function politica(): string
    {
        [$vite, $viteWs] = $this->servidorVite();

        $directivas = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", ...self::CDN_SCRIPTS, ...$vite],
            'style-src' => ["'self'", "'unsafe-inline'", ...self::CDN_ESTILOS, ...$vite],
            'font-src' => ["'self'", 'data:', ...self::CDN_FUENTES],
            'img-src' => ["'self'", 'data:', 'blob:', ...self::CDN_IMAGENES],
            'connect-src' => ["'self'", ...$vite, ...$viteWs],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return collect($directivas)
            ->map(fn ($fuentes, $directiva) => $directiva . ' ' . implode(' ', array_unique($fuentes)))
            ->implode('; ');
    }

    /**
     * En desarrollo, el servidor de Vite (archivo public/hot) también sirve los recursos.
     */
    private function servidorVite(): array
    {
        $hot = public_path('hot');

        if (!is_file($hot)) {
            return [[], []];
        }

        $origen = rtrim(trim((string) file_get_contents($hot)), '/');
        $esquema = parse_url($origen, PHP_URL_SCHEME) ?: 'http';
        $puerto = parse_url($origen, PHP_URL_PORT) ?: 5173;

        // CSP no acepta direcciones IPv6 como [::1]: se agregan localhost y 127.0.0.1.
        $origenes = array_values(array_filter([
            str_contains($origen, '[') ? null : $origen,
            "{$esquema}://localhost:{$puerto}",
            "{$esquema}://127.0.0.1:{$puerto}",
        ]));

        return [$origenes, array_map(fn ($o) => preg_replace('#^http#', 'ws', $o), $origenes)];
    }
}
