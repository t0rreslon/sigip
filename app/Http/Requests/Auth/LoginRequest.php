<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt([
            'login' => $this->input('login'),
            'password' => $this->input('password'),
            'estado_cuenta' => true,
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // ISO 25010 – Responsabilidad: queda registro de los intentos fallidos.
            \App\Support\Auditoria::movimiento(
                'Login fallido',
                ['login' => Str::limit((string) $this->input('login'), 50, '')],
                \App\Models\Cuenta::where('login', $this->input('login'))->value('cuenta_id')
            );

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        \App\Support\Auditoria::movimiento(
            'Bloqueo de cuenta',
            ['login' => Str::limit((string) $this->input('login'), 50, ''), 'motivo' => 'Demasiados intentos de inicio de sesión'],
            \App\Models\Cuenta::where('login', $this->input('login'))->value('cuenta_id')
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')) . '|' . $this->ip());
    }
}
