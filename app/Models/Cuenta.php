<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Cuenta extends Authenticatable
{
    protected $table = 'cuenta';

    protected $primaryKey = 'cuenta_id';

    public $timestamps = false;

    protected $fillable = [
        'personal_id',
        'login',
        'clave',
        'tipo_cuenta_id',
        'estado_cuenta',
    ];

    protected $hidden = [
        'clave',
    ];

    protected function casts(): array
    {
        return [
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
}