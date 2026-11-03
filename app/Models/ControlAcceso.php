<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlAcceso extends Model
{
    protected $table = 'acceso';
    protected $primaryKey = 'acceso_id';
    public $timestamps = false;

    protected $fillable = [
        'fecha_ingreso',
        'fecha_salida',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'fecha_salida' => 'datetime',
        ];
    }
}
