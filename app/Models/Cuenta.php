<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Cuenta extends Authenticatable
{
    protected $table = 'cuenta';

    protected $primaryKey = 'cuenta_id';

    public $timestamps = false;

    // Columna de la contraseña (Laravel la usa al volver a cifrar la clave).
    protected $authPasswordName = 'clave';

    // La tabla "cuenta" no tiene columna remember_token.
    protected $rememberTokenName = '';

    protected $fillable = [
        'personal_id',
        'login',
        'clave',
        'tipo_cuenta_id',
        'estado_cuenta',
    ];

    protected $hidden = [
        'clave',
        'doble_factor_secreto',
    ];

    protected function casts(): array
    {
        return [
            'doble_factor_activo' => 'boolean',
            'clave' => 'hashed',
            'fecha_creacion' => 'datetime',
            'estado_cuenta' => 'boolean',
        ];
    }

    public function getAuthPassword()
    {
        return $this->clave;
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    public function tipoCuenta()
    {
        return $this->belongsTo(TipoCuenta::class, 'tipo_cuenta_id', 'tipo_cuenta_id');
    }

    public function nombreTipo(): ?string
    {
        return $this->tipoCuenta?->nombre_cuenta;
    }

    public function tieneTipo(string ...$tipos): bool
    {
        return in_array($this->nombreTipo(), $tipos, true);
    }
}
