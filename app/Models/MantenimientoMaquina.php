<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MantenimientoMaquina extends Model
{
    protected $table = 'mantenimiento_maquina';

    protected $primaryKey = 'mantenimiento_maquina_id';

    public $timestamps = false;

    protected $fillable = [
        'maquina_id',
        'fecha_mantenimiento',
        'detalles_mantenimiento',
        'estado_maquina_mantenimiento_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_mantenimiento' => 'date',
        ];
    }

    public function maquina()
    {
        return $this->belongsTo(
            Maquina::class,
            'maquina_id',
            'maquina_id'
        );
    }

    public function estado()
    {
        return $this->belongsTo(
            EstadoMaquinaMantenimiento::class,
            'estado_maquina_mantenimiento_id',
            'estado_maquina_mantenimiento_id'
        );
    }
}