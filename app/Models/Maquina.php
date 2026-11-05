<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    protected $table = 'maquina';

    protected $primaryKey = 'maquina_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_maquina',
        'descripcion',
        'categoria_maquina_id',
        'espacio_id',
        'estado_maquina_id',
    ];

    public function categoria()
    {
        return $this->belongsTo(
            CategoriaMaquina::class,
            'categoria_maquina_id',
            'categoria_maquina_id'
        );
    }

    public function espacio()
    {
        return $this->belongsTo(
            Espacio::class,
            'espacio_id',
            'espacio_id'
        );
    }

    public function estado()
    {
        return $this->belongsTo(
            EstadoMaquina::class,
            'estado_maquina_id',
            'estado_maquina_id'
        );
    }

    public function mantenimientos()
    {
        return $this->hasMany(
            MantenimientoMaquina::class,
            'maquina_id',
            'maquina_id'
        );
    }
}