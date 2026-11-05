<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoImplemento extends Model
{
    protected $table = 'prestamo_implemento';

    protected $primaryKey = 'prestamo_implemento_id';

    public $timestamps = false;

    protected $fillable = [
        'implemento_id',
        'prestamo_id',
        'estado_implemento_devuelto_id',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(
            Prestamo::class,
            'prestamo_id',
            'prestamo_id'
        );
    }

    public function implemento(): BelongsTo
    {
        return $this->belongsTo(
            Implemento::class,
            'implemento_id',
            'implemento_id'
        );
    }

    public function estadoDevuelto(): BelongsTo
    {
        return $this->belongsTo(
            EstadoImplementoDevuelto::class,
            'estado_implemento_devuelto_id',
            'estado_implemento_devuelto_id'
        );
    }
}