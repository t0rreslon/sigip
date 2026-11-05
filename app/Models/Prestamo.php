<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamo extends Model
{
    protected $table = 'prestamo';

    protected $primaryKey = 'prestamo_id';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'personal_id',
        'fecha_prestado',
        'fecha_devuelto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_prestado' => 'datetime',
            'fecha_devuelto' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    public function implementos(): HasMany
    {
        return $this->hasMany(
            PrestamoImplemento::class,
            'prestamo_id',
            'prestamo_id'
        );
    }
}