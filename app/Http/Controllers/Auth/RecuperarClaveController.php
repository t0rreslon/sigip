<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * HU-195 / HU-206: recuperación segura de la contraseña.
 *
 * 1. La persona escribe su usuario.
 * 2. Si la cuenta está activa y tiene correo, se envía un enlace de un solo uso
 *    que vence en 60 minutos. El token se guarda cifrado (hash) en la caché.
 * 3. La respuesta es siempre la misma, para no revelar qué usuarios existen.
 */
class RecuperarClaveController extends Controller
{
    private const MINUTOS_VIGENCIA = 60;

    public function solicitar()
    {
        return view('auth.recuperar-clave');
    }

    public function enviar(Request $request)
    {
        $datos = $request->validate(['login' => ['required', 'string', 'max:50']]);

        $clave = 'recuperar-clave|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Demasiados intentos. Espera ' . ceil(RateLimiter::availableIn($clave) / 60) . ' minuto(s).',
            ]);
        }

        RateLimiter::hit($clave, 900);

        $cuenta = DB::table('cuenta as c')
            ->join('personal as p', 'p.personal_id', '=', 'c.personal_id')
            ->where('c.login', $datos['login'])
            ->where('c.estado_cuenta', true)
            ->select('c.cuenta_id', 'c.login', 'p.nombre', 'p.correo')
            ->first();

        if ($cuenta && $cuenta->correo) {
            $token = Str::random(64);

            Cache::put($this->claveCache($cuenta->cuenta_id), Hash::make($token), now()->addMinutes(self::MINUTOS_VIGENCIA));

            $enlace = route('password.reset', ['cuenta' => $cuenta->cuenta_id, 'token' => $token]);

            Mail::raw(
                "Hola {$cuenta->nombre}:\n\n"
                . "Recibimos una solicitud para restablecer la contraseña de tu cuenta \"{$cuenta->login}\" en SIGIP.\n\n"
                . "Abre este enlace para crear una nueva contraseña (vence en " . self::MINUTOS_VIGENCIA . " minutos):\n{$enlace}\n\n"
                . "Si no fuiste tú, ignora este mensaje: tu contraseña no cambiará.\n\nBienestar Universitario · Pascual Bravo",
                fn ($mensaje) => $mensaje->to($cuenta->correo)->subject('SIGIP · Restablecer contraseña')
            );
        }

        return back()->with('status', 'Si el usuario existe y tiene un correo registrado, enviamos un enlace para restablecer la contraseña. Revisa tu bandeja de entrada.');
    }

    public function formulario(int $cuenta, string $token)
    {
        abort_unless($this->tokenValido($cuenta, $token), 403, 'El enlace no es válido o ya venció. Solicita uno nuevo.');

        return view('auth.restablecer-clave', compact('cuenta', 'token'));
    }

    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'cuenta' => ['required', 'integer'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (!$this->tokenValido((int) $datos['cuenta'], $datos['token'])) {
            throw ValidationException::withMessages(['password' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);
        }

        DB::table('cuenta')->where('cuenta_id', $datos['cuenta'])->update(['clave' => Hash::make($datos['password'])]);
        Cache::forget($this->claveCache((int) $datos['cuenta']));

        Auditoria::movimiento('Actualizar', ['ruta' => 'password.store', 'detalle' => 'Restableció la contraseña'], (int) $datos['cuenta']);

        return redirect()->route('login')->with('status', 'Tu contraseña se restableció. Ya puedes iniciar sesión.');
    }

    private function tokenValido(int $cuentaId, string $token): bool
    {
        $hash = Cache::get($this->claveCache($cuentaId));

        return $hash && Hash::check($token, $hash);
    }

    private function claveCache(int $cuentaId): string
    {
        return "recuperar-clave:{$cuentaId}";
    }
}
