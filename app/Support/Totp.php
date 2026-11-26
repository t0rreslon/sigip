<?php

namespace App\Support;

/**
 * Códigos de un solo uso basados en tiempo (TOTP, RFC 6238), compatibles con
 * Google Authenticator, Microsoft Authenticator, Authy, etc.
 * Se usan para el doble factor de las cuentas de administrador (ISO 25010: autenticidad).
 */
class Totp
{
    private const ALFABETO = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIODO = 30;

    private const DIGITOS = 6;

    // Se aceptan los códigos del intervalo anterior y del siguiente (desfase del reloj del celular).
    private const VENTANA = 1;

    public static function generarSecreto(): string
    {
        return self::base32(random_bytes(20));
    }

    /**
     * Enlace otpauth:// que las apps de autenticación aceptan al ingresar la clave manualmente.
     */
    public static function uri(string $secreto, string $cuenta, string $emisor = 'SIGIP'): string
    {
        return 'otpauth://totp/' . rawurlencode("{$emisor}:{$cuenta}")
            . '?secret=' . $secreto . '&issuer=' . rawurlencode($emisor)
            . '&digits=' . self::DIGITOS . '&period=' . self::PERIODO;
    }

    public static function codigo(string $secreto, ?int $intervalo = null): string
    {
        $intervalo ??= intdiv(time(), self::PERIODO);
        $hash = hash_hmac('sha1', pack('J', $intervalo), self::decodificar($secreto), true);
        $desplazamiento = ord($hash[19]) & 0x0F;
        $numero = ((ord($hash[$desplazamiento]) & 0x7F) << 24)
            | (ord($hash[$desplazamiento + 1]) << 16)
            | (ord($hash[$desplazamiento + 2]) << 8)
            | ord($hash[$desplazamiento + 3]);

        return str_pad((string) ($numero % (10 ** self::DIGITOS)), self::DIGITOS, '0', STR_PAD_LEFT);
    }

    /**
     * Devuelve el intervalo del código si es válido (para impedir que se reutilice), o null.
     */
    public static function verificar(string $secreto, string $codigo): ?int
    {
        $codigo = preg_replace('/\D/', '', $codigo);

        if (strlen($codigo) !== self::DIGITOS) {
            return null;
        }

        $actual = intdiv(time(), self::PERIODO);

        for ($i = -self::VENTANA; $i <= self::VENTANA; $i++) {
            if (hash_equals(self::codigo($secreto, $actual + $i), $codigo)) {
                return $actual + $i;
            }
        }

        return null;
    }

    /**
     * La clave en grupos de 4 para copiarla a mano en el celular.
     */
    public static function formatear(string $secreto): string
    {
        return trim(chunk_split($secreto, 4, ' '));
    }

    private static function base32(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $salida = '';

        foreach (str_split($bits, 5) as $grupo) {
            $salida .= self::ALFABETO[bindec(str_pad($grupo, 5, '0'))];
        }

        return $salida;
    }

    private static function decodificar(string $secreto): string
    {
        $bits = '';

        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secreto))) as $caracter) {
            $bits .= str_pad(decbin(strpos(self::ALFABETO, $caracter)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split($bits, 8) as $grupo) {
            if (strlen($grupo) === 8) {
                $bytes .= chr(bindec($grupo));
            }
        }

        return $bytes;
    }
}
