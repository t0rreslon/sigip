<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
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

        // Buscar por documento, nombre, apellido o correo.

        // Buscar por documento, nombre, apellido, correo o nombre completo.
        if ($request->filled('buscar')) {
            $termino = trim($request->input('buscar'));

            $consulta->where(function ($query) use ($termino) {
                $query->where('usuario.documento', 'ILIKE', "%{$termino}%")
                    ->orWhere('usuario.nombre', 'ILIKE', "%{$termino}%")
                    ->orWhere('usuario.apellido', 'ILIKE', "%{$termino}%")
                    ->orWhere('usuario.correo', 'ILIKE', "%{$termino}%")
                    ->orWhereRaw(
                        "CONCAT_WS(' ', usuario.nombre, usuario.apellido) ILIKE ?",
                        ["%{$termino}%"]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', usuario.apellido, usuario.nombre) ILIKE ?",
                        ["%{$termino}%"]
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
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],

            'tipo_documento_id' => [
                'required',
                'integer',
                'exists:tipo_documento,tipo_documento_id'
            ],

            'documento' => [
                'required',
                'string',
                'regex:/^[0-9]{8,11}$/',
                'unique:usuario,documento'
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
                'regex:/^[A-Za-z0-9]+(?:[._%+-][A-Za-z0-9]+)*@[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)*\.[A-Za-z]{2,}$/'
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:11'
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
        ]);

        // Los usuarios nuevos se registran como activos.
        $datos['estado_usuario'] = true;

        Usuario::create($datos);

        return redirect()
            ->route('usuarios')
            ->with('success', 'Usuario registrado correctamente.');
    }
}
