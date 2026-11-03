<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index()
    {
        // Usuarios registrados
        $usuarios = Usuario::query()
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15);

        // Contadores
        $totalUsuarios = Usuario::count();
        $usuariosActivos = Usuario::where('estado_usuario', true)->count();
        $usuariosInactivos = Usuario::where('estado_usuario', false)->count();

        // Catálogos para los select
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
                'max:12',
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
                'nullable',
                'email',
                'max:80'
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

        // Por ahora, todo usuario nuevo se registra como activo.
        $datos['estado_usuario'] = true;

        Usuario::create($datos);

        return redirect()
            ->route('usuarios')
            ->with('success', 'Usuario registrado correctamente.');
    }
}
