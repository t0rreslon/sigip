<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\Auditoria;
use App\Support\PdfReporte;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $consulta = $this->consultaFiltrada($request);

        // Ordenar y paginar conservando los filtros.
        $usuarios = $consulta
            ->orderBy('usuario.apellido')
            ->orderBy('usuario.nombre')
            ->paginate(15)
            ->withQueryString();

        // Contadores generales, independientes de los filtros.
        $totalUsuarios = Usuario::count();

        $usuariosActivos = Usuario::where('estado_usuario', true)->count();

        $usuariosInactivos = Usuario::where('estado_usuario', false)->count();

        // Catálogos para los formularios y filtros.
        $tiposDocumento = DB::table('tipo_documento')
            ->orderBy('nombre_tipo_documento')
            ->get();

        $generos = DB::table('genero')
            ->orderBy('nombre_genero')
            ->get();

        $eps = DB::table('eps')
            ->orderBy('nombre_eps')
            ->get();

        $rolesUsuario = DB::table('rol_usuario')
            ->where('estado', true)
            ->orderBy('nombre')
            ->get();

        $instituciones = DB::table('institucion')
            ->where('estado_convenio', true)
            ->orderBy('nombre_institucion')
            ->get();

        return view('usuarios', compact(
            'usuarios',
            'totalUsuarios',
            'usuariosActivos',
            'usuariosInactivos',
            'tiposDocumento',
            'generos',
            'eps',
            'rolesUsuario',
            'instituciones'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas() + [
            // Ley 1581 de 2012: sin autorización no se pueden guardar los datos personales.
            'autoriza_datos' => ['accepted'],
        ], [
            'autoriza_datos.accepted' => 'Para registrar al usuario debe autorizar el tratamiento de sus datos personales.',
        ]);

        unset($datos['autoriza_datos']);
        $datos['autoriza_datos_en'] = now();

        // Los usuarios nuevos se registran como activos.
        $datos['estado_usuario'] = true;

        $usuario = Usuario::create($datos);

        Auditoria::cambio('usuario', 'INSERT', $usuario->usuario_id, null, $usuario->getAttributes());

        return redirect()
            ->route('usuarios')
            ->with('success', 'Usuario registrado correctamente.');
    }

    // HU-29: Modificar los datos de un usuario.
    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $datos = $request->validateWithBag(
            'editar',
            $this->reglas($usuario->usuario_id, $usuario->correo) + [
                'estado_usuario' => ['required', 'boolean'],
            ]
        );

        $antes = $usuario->getAttributes();
        $usuario->update($datos);

        Auditoria::cambio('usuario', 'UPDATE', $usuario->usuario_id, $antes, $usuario->fresh()->getAttributes());

        return redirect()
            ->route('usuarios', $request->only(['buscar', 'rol', 'estado', 'institucion', 'genero', 'page']))
            ->with('success', 'Los datos del usuario se actualizaron correctamente.');
    }

    // HU-144: activar o desactivar un usuario.
    public function cambiarEstado(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $antes = ['estado_usuario' => (bool) $usuario->estado_usuario];

        $usuario->estado_usuario = !$usuario->estado_usuario;
        $usuario->save();

        Auditoria::cambio('usuario', 'ESTADO', $usuario->usuario_id, $antes, ['estado_usuario' => (bool) $usuario->estado_usuario]);

        return redirect()
            ->route('usuarios', $request->only(['buscar', 'rol', 'estado', 'institucion', 'genero', 'page']))
            ->with('success', "{$usuario->nombre} {$usuario->apellido} quedó " . ($usuario->estado_usuario ? 'activo.' : 'inactivo.'));
    }

    // HU-193: historial de cambios de un usuario.
    public function historial($id)
    {
        $usuario = Usuario::findOrFail($id);

        return view('historial', [
            'titulo' => 'Historial de cambios',
            'subtitulo' => "{$usuario->nombre} {$usuario->apellido} · {$usuario->documento}",
            'volver' => route('usuarios'),
            'registros' => Auditoria::historial('usuario', $usuario->usuario_id),
        ]);
    }

    // HU-192: exportar los usuarios (con los filtros aplicados) a PDF.
    public function exportar(Request $request)
    {
        $usuarios = $this->consultaFiltrada($request)
            ->orderBy('usuario.apellido')
            ->orderBy('usuario.nombre')
            ->get();

        Auditoria::movimiento('Exportar', ['reporte' => 'usuarios', 'filtros' => $request->query()]);

        $pdf = PdfReporte::tabla(
            'Usuarios del gimnasio',
            [
                ['titulo' => 'Documento', 'ancho' => 80],
                ['titulo' => 'Nombre', 'ancho' => 170],
                ['titulo' => 'Correo', 'ancho' => 180],
                ['titulo' => 'Teléfono', 'ancho' => 75],
                ['titulo' => 'Rol', 'ancho' => 80],
                ['titulo' => 'Institución', 'ancho' => 140],
                ['titulo' => 'Estado', 'ancho' => 57],
            ],
            $usuarios->map(fn ($u) => [
                $u->documento,
                "{$u->nombre} {$u->apellido}",
                $u->correo,
                $u->telefono ?? '',
                $u->nombre_rol ?? '',
                $u->nombre_institucion ?? '',
                $u->estado_usuario ? 'Activo' : 'Inactivo',
            ]),
            ['Total de usuarios: ' . $usuarios->count()]
        );

        return PdfReporte::descargar($pdf, 'usuarios-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * Consulta de usuarios con los filtros de la vista (HU-30); la usan el listado y el PDF.
     */
    private function consultaFiltrada(Request $request)
    {
        // Consulta principal con los datos del rol y la institución.
        $consulta = DB::table('usuario')
            ->leftJoin(
                'rol_usuario',
                'usuario.rol_usuario_id',
                '=',
                'rol_usuario.rol_usuario_id'
            )
            ->leftJoin(
                'institucion',
                'usuario.institucion_id',
                '=',
                'institucion.institucion_id'
            )
            ->select(
                'usuario.*',
                'rol_usuario.nombre as nombre_rol',
                'institucion.nombre_institucion'
            );

        // Buscar por documento, nombre, apellido, correo o nombre completo.
        if ($request->filled('buscar')) {
            $termino = trim($request->input('buscar'));

            $consulta->where(function ($query) use ($termino) {
                $query->where('usuario.documento', 'ILIKE', \App\Support\Busqueda::contiene($termino))
                    ->orWhere('usuario.nombre', 'ILIKE', \App\Support\Busqueda::contiene($termino))
                    ->orWhere('usuario.apellido', 'ILIKE', \App\Support\Busqueda::contiene($termino))
                    ->orWhere('usuario.correo', 'ILIKE', \App\Support\Busqueda::contiene($termino))
                    ->orWhereRaw(
                        "CONCAT_WS(' ', usuario.nombre, usuario.apellido) ILIKE ?",
                        [\App\Support\Busqueda::contiene($termino)]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', usuario.apellido, usuario.nombre) ILIKE ?",
                        [\App\Support\Busqueda::contiene($termino)]
                    );
            });
        }


        // Filtrar por rol.
        if ($request->filled('rol')) {
            $consulta->where(
                'usuario.rol_usuario_id',
                $request->input('rol')
            );
        }

        // Filtrar por estado: 1 = activo, 0 = inactivo.
        if ($request->filled('estado')) {
            $consulta->where(
                'usuario.estado_usuario',
                $request->input('estado') === '1'
            );
        }

        // Filtrar por institución (si se utiliza ese filtro en la vista).
        if ($request->filled('institucion')) {
            $consulta->where(
                'usuario.institucion_id',
                $request->input('institucion')
            );
        }

        // Filtrar por género (si se utiliza ese filtro en la vista).
        if ($request->filled('genero')) {
            $consulta->where(
                'usuario.genero_id',
                $request->input('genero')
            );
        }

        return $consulta;
    }

    /**
     * Reglas de validación compartidas por el registro y la edición.
     * Al editar se ignora el propio usuario en las validaciones de unicidad.
     */
    private function reglas(?int $usuarioId = null, ?string $correoActual = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\'.-]+$/u'],
            'apellido' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\'.-]+$/u'],

            'tipo_documento_id' => [
                'required',
                'integer',
                'exists:tipo_documento,tipo_documento_id'
            ],
            'documento' => [
                'required',
                'string',
                'regex:/^[0-9]{8,11}$/',
                Rule::unique('usuario', 'documento')->ignore($usuarioId, 'usuario_id'),

                function ($attribute, $value, $fail) {
                    $existeEnPersonal = DB::table('personal')
                        ->whereRaw('BTRIM(documento) = ?', [trim($value)])
                        ->exists();

                    if ($existeEnPersonal) {
                        $fail('Este documento ya se encuentra registrado en el personal del gimnasio.');
                    }
                },
            ],

            'genero_id' => [
                'required',
                'integer',
                'exists:genero,genero_id'
            ],

            'eps_id' => [
                'nullable',
                'integer',
                'exists:eps,eps_id'
            ],

            'correo' => [
                'required',
                'string',
                'email',
                'max:80',
                'regex:/^[A-Za-z0-9]+(?:[.\_%+-][A-Za-z0-9]+)*@[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)*\.[A-Za-z]{2,}$/',

                function ($attribute, $value, $fail) use ($usuarioId, $correoActual) {
                    // Si al editar no se cambió el correo, no se vuelve a validar
                    // (hay registros antiguos con correos repetidos).
                    if ($correoActual !== null && mb_strtolower(trim($value)) === mb_strtolower(trim($correoActual))) {
                        return;
                    }

                    $existe = DB::table('usuario')
                        ->whereRaw('LOWER(BTRIM(correo)) = ?', [mb_strtolower(trim($value))])
                        ->when($usuarioId, fn ($q) => $q->where('usuario_id', '!=', $usuarioId))
                        ->exists();

                    if ($existe) {
                        $fail('Este correo electrónico ya se encuentra registrado.');
                    }
                },
            ],

            'telefono' => [
                'nullable',
                'string',
                'regex:/^[0-9]{7,11}$/'
            ],

            'fecha_nacimiento' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ],

            'rol_usuario_id' => [
                'required',
                'integer',
                'exists:rol_usuario,rol_usuario_id,estado,1'
            ],

            'institucion_id' => [
                'required',
                'integer',
                'exists:institucion,institucion_id,estado_convenio,1'
            ],
        ];
    }
}
