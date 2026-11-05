<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pago';

    protected $primaryKey = 'pago_id';

    public $timestamps = false;

    protected $fillable = [
        'suscripcion_id',
        'metodo_pago_id',
        'monto',
        'fecha_pago',
        'estado_pago',
        'fecha_revision'
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'datetime',
            'fecha_revision' => 'datetime',
        ];
    }

    public function suscripcion()
    {
        return $this->belongsTo(
            Suscripcion::class,
            'suscripcion_id'
        );
    }

    public function metodoPago()
    {
        return $this->belongsTo(
            MetodoPago::class,
            'metodo_pago_id'
        );
    }
}
