<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Implemento extends Model
{
    protected $table = 'implemento';

    protected $primaryKey = 'implemento_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_implemento',
        'categoria_implemento_id',
        'estado_implemento_id',
    ];

    public function categoria()
    {
        return $this->belongsTo(
            CategoriaImplemento::class,
            'categoria_implemento_id',
            'categoria_implemento_id'
        );
    }

    public function estado()
    {
        return $this->belongsTo(
            EstadoImplemento::class,
            'estado_implemento_id',
            'estado_implemento_id'
        );
    }

    public function prestamos()
    {
        return $this->hasMany(
            PrestamoImplemento::class,
            'implemento_id',
            'implemento_id'
        );
    }
}