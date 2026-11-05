<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    protected $table = 'suscripcion';

    protected $primaryKey = 'suscripcion_id';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'plan_id',
        'fecha_inicio',
        'fecha_fin',
        'cantidad_dias_restantes',
        'estado'
    ];

    public function usuario()
{
    return $this->belongsTo(Usuario::class, 'usuario_id');
}

public function plan()
{
    return $this->belongsTo(Plan::class, 'plan_id');
}
}