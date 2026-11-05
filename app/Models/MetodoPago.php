<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodo_pago';

    protected $primaryKey = 'metodo_pago_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_metodo',
        'estado_metodo'
    ];

    protected function casts(): array
    {
        return [
            'estado_metodo' => 'boolean',
        ];
    }

    public function pagos()
    {
        return $this->hasMany(
            Pago::class,
            'metodo_pago_id'
        );
    }
}
