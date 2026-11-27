<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Suscripcion extends Model
{
    protected $table = 'suscripcion';

    protected $primaryKey = 'suscripcion_id';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'plan_id',
        'fecha_inicio',
        'fecha_fin',
        'cantidad_dias_restantes',
        'estado'
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'estado' => 'boolean',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'suscripcion_id', 'suscripcion_id');
    }

    /**
     * Expresión SQL con el estado calculado de una suscripción (alias "s", plan "pl"):
     * vencida, activa o por_activar.
     */
    public static function sqlEstadoCalculado(): string
    {
        return "CASE
            WHEN (s.fecha_fin IS NOT NULL AND s.fecha_fin < NOW())
              OR (pl.tipo_plan = 'tiquetera' AND s.cantidad_dias_restantes <= 0)
                THEN 'vencida'
            WHEN s.estado THEN 'activa'
            ELSE 'por_activar'
        END";
    }

    public function totalVerificado(): float
    {
        return (float) DB::table('pago')
            ->where('suscripcion_id', $this->suscripcion_id)
            ->where('estado_pago', 'Verificado')
            ->sum('monto');
    }

    public function estaVencida(): bool
    {
        $plan = $this->plan;

        if ($this->fecha_fin !== null && $this->fecha_fin->isPast()) {
            return true;
        }

        return $plan && $plan->tipo_plan === 'tiquetera'
            && (int) $this->cantidad_dias_restantes <= 0;
    }

    /**
     * La suscripción queda activa solo cuando el valor del plan está pagado
     * (pagos verificados) y sigue vigente. Se llama después de registrar,
     * modificar o verificar un pago.
     */
    public function sincronizarEstado(): void
    {
        $this->loadMissing('plan');

        $pagada = $this->totalVerificado() >= (float) $this->plan->costo;

        $estado = $pagada && !$this->estaVencida();

        if ($this->estado !== $estado) {
            // Al activarse (pago completo) empieza la vigencia: ese día es la fecha de inicio.
            if ($estado) {
                $this->iniciarVigencia();
            }

            $this->estado = $estado;
            $this->save();
        }
    }

    /**
     * Fechas automáticas al activarse:
     * - Suscripción: inicia hoy y vence al cumplirse los días de vigencia del plan.
     * - Tiquetera: inicia hoy; la fecha de fin se registra cuando se usa la última entrada.
     */
    public function iniciarVigencia(): void
    {
        $this->loadMissing('plan');
        $inicio = now();

        $this->fecha_inicio = $inicio;

        if ($this->plan->tipo_plan === 'suscripcion') {
            $dias = max(1, (int) $this->plan->cantidad_entradas);
            $this->fecha_fin = $inicio->copy()->addDays($dias - 1)->endOfDay();
            $this->cantidad_dias_restantes = $dias;
        } else {
            $this->fecha_fin = null;
        }
    }
}
