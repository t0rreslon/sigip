<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuario';
    protected $primaryKey = 'usuario_id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'tipo_documento_id',
        'documento',
        'genero_id',
        'eps_id',
        'correo',
        'telefono',
        'fecha_nacimiento',
        'rol_usuario_id',
        'institucion_id',
        'estado_usuario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'estado_usuario' => 'boolean',
        ];
    }

}



