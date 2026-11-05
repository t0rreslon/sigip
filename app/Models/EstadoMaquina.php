<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoMaquina extends Model
{
    protected $table = 'estado_maquina';

    protected $primaryKey = 'estado_maquina_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_estado',
    ];
}
