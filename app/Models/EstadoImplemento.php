<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoImplemento extends Model
{
    protected $table = 'estado_implemento';

    protected $primaryKey = 'estado_implemeto_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_estado',
    ];
}

