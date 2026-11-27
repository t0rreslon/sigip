<?php

namespace App\Http\Controllers;

use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Consulta de la auditoría del sistema (ISO 25010 – Seguridad: responsabilidad y no repudio).
 * - index: página del Administrador con todos los cambios en registros (auditoria_tablas)
 *   y todas las acciones de las cuentas (auditoria_movimientos), más el estado de la
 *   cadena de hashes que protege ambas tablas.
 * - registro: historial de un pago, una suscripción o una reserva para el personal.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'vista' => ['nullable', Rule::in(['cambios', 'acciones'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'modulo' => ['nullable', Rule::in(array_keys(Auditoria::MODULOS))],
            'operacion' => ['nullable', Rule::in(array_keys(Auditoria::OPERACIONES))],
            'usuario' => ['nullable', 'string', 'max:100'],
            'registro' => ['nullable', 'string', 'max:100'],
            'cuenta' => ['nullable', 'integer'],
            'tipo_cuenta' => ['nullable', 'integer'],
            'accion' => ['nullable', 'integer'],
        ]);

        $vista = $filtros['vista'] ?? 'cambios';
        $desde = $this->fecha($filtros['desde'] ?? null, now()->subDays(29))->startOfDay();
        $hasta = $this->fecha($filtros['hasta'] ?? null, now())->endOfDay();

        $datos = [
            'vista' => $vista,
            'desde' => $desde,
            'hasta' => $hasta,
            'integridad' => Auditoria::verificarIntegridad(),
            'resumen' => [
                'cambios' => DB::table('auditoria_tablas')->whereBetween('creado_en', [$desde, $hasta])->count(),
                'acciones' => DB::table('auditoria_movimientos')->whereBetween('fecha_hora', [$desde, $hasta])->count(),
                'fallidos' => DB::table('auditoria_movimientos as m')
                    ->join('tipo_accion as t', 't.tipo_accion_id', '=', 'm.tipo_accion_id')
                    ->whereIn('t.nombre_accion', ['Login fallido', 'Bloqueo de cuenta'])
                    ->whereBetween('m.fecha_hora', [$desde, $hasta])
                    ->count(),
            ],
            'idLoginFallido' => DB::table('tipo_accion')->where('nombre_accion', 'Login fallido')->value('tipo_accion_id'),
        ];

        return view('auditoria', $datos + ($vista === 'cambios'
            ? $this->cambios($filtros, $desde, $hasta)
            : $this->acciones($filtros, $desde, $hasta)));
    }

    // Historial de un registro que no tiene página de detalle propia.
    public function registro(string $modulo, string $id)
    {
        $titulos = ['pago' => 'Historial del pago', 'suscripcion' => 'Historial de la suscripción', 'reserva' => 'Historial de la reserva'];
        $volver = ['pago' => 'pago.index', 'suscripcion' => 'planes.index', 'reserva' => 'reservas.index'];

        $tablaId = $modulo . '_id';
        abort_unless(DB::table($modulo)->where($tablaId, $id)->exists(), 404);

        return view('historial', [
            'titulo' => "{$titulos[$modulo]} #{$id}",
            'subtitulo' => 'Quién registró y modificó este registro, cuándo y qué cambió.',
            'volver' => route($volver[$modulo]),
            'registros' => Auditoria::historial($modulo, $id),
        ]);
    }

    private function cambios(array $filtros, Carbon $desde, Carbon $hasta): array
    {
        $registros = DB::table('auditoria_tablas')
            ->whereBetween('creado_en', [$desde, $hasta])
            ->when($filtros['modulo'] ?? null, fn ($q, $v) => $q->where('nombre_tabla', $v))
            ->when($filtros['operacion'] ?? null, fn ($q, $v) => $q->where('operacion', $v))
            ->when($filtros['usuario'] ?? null, fn ($q, $v) => $q->where('usuario', $v))
            ->when($filtros['registro'] ?? null, fn ($q, $v) => $q->where('registro_id', ltrim($v, '#')))
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $registros->getCollection()->transform(function ($fila) {
            $fila->anteriores = json_decode($fila->datos_anteriores ?? 'null', true) ?? [];
            $fila->nuevos = json_decode($fila->datos_nuevos ?? 'null', true) ?? [];
            $fila->modulo = Auditoria::MODULOS[$fila->nombre_tabla] ?? $fila->nombre_tabla;
            $fila->enlace = $fila->registro_id ? Auditoria::enlace($fila->nombre_tabla, $fila->registro_id) : null;

            return $fila;
        });

        return [
            'registros' => $registros,
            'usuarios' => DB::table('auditoria_tablas')->whereNotNull('usuario')->distinct()->orderBy('usuario')->pluck('usuario'),
        ];
    }

    private function acciones(array $filtros, Carbon $desde, Carbon $hasta): array
    {
        $movimientos = DB::table('auditoria_movimientos as m')
            ->leftJoin('cuenta as c', 'c.cuenta_id', '=', 'm.cuenta_id')
            ->leftJoin('personal as p', 'p.personal_id', '=', 'c.personal_id')
            ->leftJoin('tipo_cuenta as tc', 'tc.tipo_cuenta_id', '=', 'c.tipo_cuenta_id')
            ->join('tipo_accion as t', 't.tipo_accion_id', '=', 'm.tipo_accion_id')
            ->whereBetween('m.fecha_hora', [$desde, $hasta])
            ->when($filtros['cuenta'] ?? null, fn ($q, $v) => $q->where('m.cuenta_id', $v))
            ->when($filtros['tipo_cuenta'] ?? null, fn ($q, $v) => $q->where('c.tipo_cuenta_id', $v))
            ->when($filtros['accion'] ?? null, fn ($q, $v) => $q->where('m.tipo_accion_id', $v))
            ->select('m.*', 'c.login', 'p.nombre', 'p.apellido', 'tc.nombre_cuenta', 't.nombre_accion')
            ->orderByDesc('m.fecha_hora')
            ->orderByDesc('m.auditoria_id')
            ->paginate(20)
            ->withQueryString();

        $movimientos->getCollection()->transform(function ($m) {
            $m->detalle = json_decode($m->datos, true) ?? [];

            return $m;
        });

        return [
            'movimientos' => $movimientos,
            'cuentas' => DB::table('cuenta')->orderBy('login')->get(['cuenta_id', 'login']),
            'tiposCuenta' => DB::table('tipo_cuenta')->orderBy('nombre_cuenta')->get(['tipo_cuenta_id', 'nombre_cuenta']),
            'acciones' => DB::table('tipo_accion')->orderBy('nombre_accion')->get(),
        ];
    }

    private function fecha(?string $valor, Carbon $porDefecto): Carbon
    {
        return $valor ? Carbon::createFromFormat('Y-m-d', $valor) : $porDefecto;
    }
}
