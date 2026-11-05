<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'personal';

    protected $primaryKey = 'personal_id';

    public $timestamps = false;

    protected $fillable = [
        'tipo_documento_id',
        'documento',
        'nombre',
        'apellido',
        'genero_id',
        'fecha_inicio',
        'telefono',
        'correo',
        'estado_personal_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
        ];
    }
}