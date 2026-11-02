<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reserva';
    protected $primaryKey = 'reserva_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'fecha_creacion',
        'usuario_id',
        'personal_id',
        'espacio_id',
        'fecha_inicio',
        'fecha_fin',
        'estado_reserva_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión de tipos
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    public function espacio()
    {
        return $this->belongsTo(Espacio::class, 'espacio_id', 'espacio_id');
    }

    public function estadoReserva()
    {
        return $this->belongsTo(
            EstadoReserva::class,
            'estado_reserva_id',
            'estado_reserva_id'
        );
    }
}