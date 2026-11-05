<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';
    protected $primaryKey = 'plan_id';
    public $timestamps = false;
    protected $fillable = [
        'nombre_plan',
        'descripcion',
        'tipo_plan',
        'cantidad_entradas',
        'costo',
        'estado_plan'

    ];

}