<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.iniciar-sesion');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Doble factor: la contraseña fue correcta, pero falta el código del celular.
        $cuenta = $request->user();

        if ($cuenta->doble_factor_activo) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put([
                DobleFactorController::SESION_PENDIENTE => $cuenta->cuenta_id,
                'doble_factor.desde' => now()->timestamp,
                'doble_factor.recordar' => $request->boolean('remember'),
            ]);

            return redirect()->route('doble-factor');
        }

        \App\Support\Auditoria::movimiento('Iniciar sesión');

        return redirect()->intended(route('index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        \App\Support\Auditoria::movimiento('Cerrar sesión');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
