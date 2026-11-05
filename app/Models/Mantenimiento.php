<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimiento';

    protected $primaryKey = 'mantenimiento_id';

    public $timestamps = false;

    protected $fillable = [
        'maquina_id',
        'fecha_mantenimiento',
        'detalles_mantenimiento',
        'estado_maquina_matenimiento_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_mantenimiento' => 'date',
        ];
    }
}
