<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Espacio extends Model
{
    protected $table = 'espacio';

    protected $primaryKey = 'espacio_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_espacio',
        'descripcion',
        'estado_espacio_id',
    ];

}

