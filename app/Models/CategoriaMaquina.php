<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaMaquina extends Model
{
    protected $table = 'categoria_maquina';

    protected $primaryKey = 'categoria_maquina_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_categoria',
        'descripcion_categoria',
        'esta_activo',
    ];

    protected function casts(): array
    {
        return [
            'esta_activo' => 'boolean',
        ];
    }
}
