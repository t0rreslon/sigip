<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoMaquinaMantenimiento extends Model
{
    protected $table = 'estado_maquina_mantenimiento';

    protected $primaryKey = 'estado_maquina_mantenimiento_id';

    public $timestamps = false;
}
