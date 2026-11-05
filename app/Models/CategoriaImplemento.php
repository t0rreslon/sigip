<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaImplemento extends Model
{
    protected $table = 'categoria_implemento';

    protected $primaryKey = 'catrgoria_implemento_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_categoria',
        'descripcion_categoria',
        'stock_minimo',
        'esta_activo',
    ];

    protected function casts(): array
    {
        return [
            'esta_activo' => 'boolean',
        ];
    }
}
