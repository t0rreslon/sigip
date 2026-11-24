<?php

namespace App\Http\Controllers;

use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Cuentas de operador (personal de recepción). Solo el Administrador.
 */
class OperadorController extends Controller
{
    // HU-187 / HU-191: lista y búsqueda de operadores.
    public function index(Request $request)
    {
        $operadores = DB::table('cuenta as c')
            ->join('personal as p', 'p.personal_id', '=', 'c.personal_id')
            ->where('c.tipo_cuenta_id', $this->tipoOperador())
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $buscar = trim($request->input('buscar'));
                $q->where(fn ($w) => $w->where('p.documento', 'ILIKE', "%{$buscar}%")
                    ->orWhere('c.login', 'ILIKE', "%{$buscar}%")
                    ->orWhereRaw("CONCAT_WS(' ', p.nombre, p.apellido) ILIKE ?", ["%{$buscar}%"]));
            })
            ->when(in_array($request->input('estado'), ['1', '0'], true), fn ($q) => $q->where('c.estado_cuenta', $request->input('estado') === '1'))
            ->select('c.cuenta_id', 'c.login', 'c.estado_cuenta', 'c.fecha_creacion', 'p.*')
            ->selectSub(
                DB::table('auditoria_movimientos')->whereColumn('cuenta_id', 'c.cuenta_id')->selectRaw('MAX(fecha_hora)'),
                'ultima_accion'
            )
            ->orderBy('p.apellido')
            ->paginate(12)
            ->withQueryString();

        return view('operadores.index', [
            'operadores' => $operadores,
            'tiposDocumento' => DB::table('tipo_documento')->orderBy('nombre_tipo_documento')->get(),
            'generos' => DB::table('genero')->orderBy('nombre_genero')->get(),
        ]);
    }

    // HU-186: crear la cuenta de un operador (registra también sus datos personales).
    public function store(Request $request)
    {
        $datos = $request->validate(InstructorController::reglasPersonal() + [
            'login' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:cuenta,login'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'login.regex' => 'El usuario solo puede tener letras, números, punto, guion y guion bajo.',
        ]);

        DB::transaction(function () use ($datos) {
            $personal = collect($datos)->except(['login', 'password'])->all();
            $personal['estado_personal_id'] = DB::table('estado_personal')->where('nombre_estado', 'Activo')->value('estado_personal_id');
            $personal['fecha_inicio'] = $personal['fecha_inicio'] ?? today()->format('Y-m-d');

            $personalId = DB::table('personal')->insertGetId($personal, 'personal_id');

            $cuentaId = DB::table('cuenta')->insertGetId([
                'personal_id' => $personalId,
                'login' => $datos['login'],
                'clave' => Hash::make($datos['password']),
                'tipo_cuenta_id' => $this->tipoOperador(),
                'estado_cuenta' => true,
            ], 'cuenta_id');

            Auditoria::cambio('cuenta', 'INSERT', $cuentaId, null, ['login' => $datos['login'], 'tipo' => 'Operador']);
        });

        return redirect()->route('operadores.index')->with('success', "Se creó la cuenta de operador \"{$datos['login']}\".");
    }

    // HU-190: activar o desactivar la cuenta de un operador.
    public function cambiarEstado($id)
    {
        $cuenta = DB::table('cuenta')->where('cuenta_id', $id)->where('tipo_cuenta_id', $this->tipoOperador())->first();
        abort_if(!$cuenta, 404);

        DB::table('cuenta')->where('cuenta_id', $id)->update(['estado_cuenta' => !$cuenta->estado_cuenta]);
        Auditoria::cambio('cuenta', 'ESTADO', $id, ['estado_cuenta' => (bool) $cuenta->estado_cuenta], ['estado_cuenta' => !$cuenta->estado_cuenta]);

        return back()->with('success', "La cuenta \"{$cuenta->login}\" quedó " . ($cuenta->estado_cuenta ? 'desactivada.' : 'activada.'));
    }

    // HU-207: historial de acciones realizadas por los operadores.
    public function movimientos(Request $request)
    {
        $desde = $this->fecha($request->input('desde'), now()->subDays(29))->startOfDay();
        $hasta = $this->fecha($request->input('hasta'), now())->endOfDay();

        $movimientos = DB::table('auditoria_movimientos as m')
            ->join('cuenta as c', 'c.cuenta_id', '=', 'm.cuenta_id')
            ->join('personal as p', 'p.personal_id', '=', 'c.personal_id')
            ->join('tipo_accion as t', 't.tipo_accion_id', '=', 'm.tipo_accion_id')
            ->where('c.tipo_cuenta_id', $this->tipoOperador())
            ->whereBetween('m.fecha_hora', [$desde, $hasta])
            ->when($request->filled('cuenta'), fn ($q) => $q->where('m.cuenta_id', $request->input('cuenta')))
            ->when($request->filled('accion'), fn ($q) => $q->where('m.tipo_accion_id', $request->input('accion')))
            ->select('m.*', 'c.login', 'p.nombre', 'p.apellido', 't.nombre_accion')
            ->orderByDesc('m.fecha_hora')
            ->paginate(20)
            ->withQueryString();

        $movimientos->getCollection()->transform(function ($m) {
            $m->detalle = json_decode($m->datos, true) ?? [];

            return $m;
        });

        return view('operadores.movimientos', [
            'movimientos' => $movimientos,
            'desde' => $desde,
            'hasta' => $hasta,
            'operadores' => DB::table('cuenta')->where('tipo_cuenta_id', $this->tipoOperador())->orderBy('login')->get(['cuenta_id', 'login']),
            'acciones' => DB::table('tipo_accion')->orderBy('nombre_accion')->get(),
        ]);
    }

    private function tipoOperador(): int
    {
        return (int) DB::table('tipo_cuenta')->where('nombre_cuenta', 'Operador')->value('tipo_cuenta_id');
    }

    private function fecha(?string $valor, Carbon $porDefecto): Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor) : $porDefecto;
        } catch (\Throwable) {
            return $porDefecto;
        }
    }
}
