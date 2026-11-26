<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Support\Auditoria;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Doble factor de autenticación (TOTP) para las cuentas de administrador.
 * ISO 25010 – Seguridad / Autenticidad.
 *
 * Al iniciar sesión, si la cuenta tiene el doble factor activo, la contraseña
 * correcta no basta: se pide el código de 6 dígitos de la app de autenticación.
 */
class DobleFactorController extends Controller
{
    public const SESION_PENDIENTE = 'doble_factor.cuenta_id';

    private const MINUTOS_PARA_INGRESAR = 5;

    /**
     * Paso 2 del inicio de sesión: formulario del código.
     */
    public function desafio(Request $request)
    {
        if (!$this->cuentaPendiente($request)) {
            return redirect()->route('login');
        }

        return view('auth.doble-factor');
    }

    public function verificar(Request $request)
    {
        $cuenta = $this->cuentaPendiente($request);

        if (!$cuenta) {
            return redirect()->route('login')->withErrors(['login' => 'La verificación venció. Inicia sesión de nuevo.']);
        }

        $request->validate(['codigo' => ['required', 'string', 'max:10']]);

        $limite = 'doble-factor|' . $cuenta->cuenta_id;

        if (RateLimiter::tooManyAttempts($limite, 5)) {
            $request->session()->forget(self::SESION_PENDIENTE);
            Auditoria::movimiento('Bloqueo de cuenta', ['motivo' => 'Códigos de doble factor incorrectos'], $cuenta->cuenta_id);

            return redirect()->route('login')->withErrors([
                'login' => 'Demasiados códigos incorrectos. Espera ' . ceil(RateLimiter::availableIn($limite) / 60) . ' minuto(s) e inicia sesión de nuevo.',
            ]);
        }

        if (!self::codigoValido($cuenta, $request->input('codigo'))) {
            RateLimiter::hit($limite, 600);
            Auditoria::movimiento('Login fallido', ['login' => $cuenta->login, 'motivo' => 'Código de doble factor incorrecto'], $cuenta->cuenta_id);

            throw ValidationException::withMessages(['codigo' => 'El código no es correcto o ya se usó.']);
        }

        RateLimiter::clear($limite);
        $recordar = (bool) $request->session()->pull('doble_factor.recordar', false);
        $request->session()->forget([self::SESION_PENDIENTE, 'doble_factor.desde']);

        Auth::login($cuenta, $recordar);
        $request->session()->regenerate();

        Auditoria::movimiento('Iniciar sesión', ['doble_factor' => true]);

        return redirect()->intended(route('index', absolute: false));
    }

    /**
     * Perfil: genera una clave nueva y la muestra para registrarla en el celular.
     */
    public function iniciarActivacion(Request $request)
    {
        $this->soloAdministrador($request);

        $request->session()->put('doble_factor.nuevo_secreto', Totp::generarSecreto());

        return redirect()->route('perfil')->withFragment('doble-factor');
    }

    public function activar(Request $request)
    {
        $this->soloAdministrador($request);

        $secreto = $request->session()->get('doble_factor.nuevo_secreto');

        if (!$secreto) {
            return redirect()->route('perfil')->withErrors(['codigo_activacion' => 'Genera la clave de nuevo.']);
        }

        $request->validate(['codigo_activacion' => ['required', 'string', 'max:10']]);

        if (Totp::verificar($secreto, $request->input('codigo_activacion')) === null) {
            throw ValidationException::withMessages(['codigo_activacion' => 'El código no coincide. Revisa que la hora del celular sea correcta.']);
        }

        $cuenta = $request->user();
        $cuenta->forceFill([
            'doble_factor_secreto' => Crypt::encryptString($secreto),
            'doble_factor_activo' => true,
        ])->save();

        $request->session()->forget('doble_factor.nuevo_secreto');
        Auditoria::movimiento('Activar 2FA');

        return redirect()->route('perfil')->with('success', 'El doble factor quedó activo. Desde ahora se pedirá el código al iniciar sesión.');
    }

    public function desactivar(Request $request)
    {
        $cuenta = $request->user();

        $request->validate(['clave_actual_2fa' => ['required', 'string']]);

        if (!Hash::check($request->input('clave_actual_2fa'), $cuenta->clave)) {
            throw ValidationException::withMessages(['clave_actual_2fa' => 'La contraseña no es correcta.']);
        }

        $cuenta->forceFill(['doble_factor_secreto' => null, 'doble_factor_activo' => false])->save();
        Auditoria::movimiento('Desactivar 2FA');

        return redirect()->route('perfil')->with('success', 'El doble factor se desactivó.');
    }

    public function cancelarActivacion(Request $request)
    {
        $request->session()->forget('doble_factor.nuevo_secreto');

        return redirect()->route('perfil');
    }

    /**
     * Comprueba el código de una cuenta e impide reutilizar el mismo código.
     */
    public static function codigoValido(Cuenta $cuenta, string $codigo): bool
    {
        if (!$cuenta->doble_factor_activo || !$cuenta->doble_factor_secreto) {
            return false;
        }

        $intervalo = Totp::verificar(Crypt::decryptString($cuenta->doble_factor_secreto), $codigo);

        if ($intervalo === null) {
            return false;
        }

        // Cache::add solo guarda si la clave no existe: un código ya usado se rechaza.
        return Cache::add("doble-factor-usado:{$cuenta->cuenta_id}:{$intervalo}", true, now()->addMinutes(3));
    }

    private function cuentaPendiente(Request $request): ?Cuenta
    {
        $id = $request->session()->get(self::SESION_PENDIENTE);
        $desde = $request->session()->get('doble_factor.desde');

        if (!$id || !$desde || now()->timestamp - $desde > self::MINUTOS_PARA_INGRESAR * 60) {
            return null;
        }

        return Cuenta::where('cuenta_id', $id)->where('estado_cuenta', true)->first();
    }

    private function soloAdministrador(Request $request): void
    {
        abort_unless($request->user()->tieneTipo('Administrador'), 403);
    }
}
