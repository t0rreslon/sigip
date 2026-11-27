<?php

namespace App\Http\Controllers;

use App\Support\Auditoria;
use App\Support\Imagenes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * HU-189: cada cuenta consulta y modifica sus datos personales y su contraseña.
 */
class PerfilController extends Controller
{
    public function show(Request $request)
    {
        $cuenta = $request->user();

        return view('perfil', [
            'cuenta' => $cuenta,
            'personal' => DB::table('personal')->where('personal_id', $cuenta->personal_id)->first(),
            'nuevoSecreto' => $request->session()->get('doble_factor.nuevo_secreto'),
        ]);
    }

    public function update(Request $request)
    {
        $cuenta = $request->user();
        $antes = DB::table('personal')->where('personal_id', $cuenta->personal_id)->first();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\'.-]+$/u'],
            'apellido' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\'.-]+$/u'],
            'telefono' => ['nullable', 'regex:/^[0-9]{7,11}$/'],
            'correo' => ['nullable', 'email', 'max:80'],
        ]);

        DB::table('personal')->where('personal_id', $cuenta->personal_id)->update($datos);
        Auditoria::cambio('personal', 'UPDATE', $cuenta->personal_id, (array) $antes, $datos);

        return back()->with('success', 'Tus datos se actualizaron.');
    }

    // Foto de perfil de la cuenta: subir una nueva o quitar la actual.
    public function cambiarFoto(Request $request)
    {
        $cuenta = $request->user();
        $personal = DB::table('personal')->where('personal_id', $cuenta->personal_id)->first();
        abort_unless($personal, 404);

        $request->validate([
            'imagen' => array_merge(['required_without:quitar_imagen'], array_slice(Imagenes::REGLAS, 1)),
            'quitar_imagen' => ['nullable', 'boolean'],
        ], ['imagen.required_without' => 'Elige una imagen para tu foto de perfil.']);

        Imagenes::borrar($personal->imagen, 'personal');
        $nueva = $request->hasFile('imagen') ? Imagenes::guardar($request->file('imagen'), 'personal') : null;

        DB::table('personal')->where('personal_id', $cuenta->personal_id)->update(['imagen' => $nueva]);
        Auditoria::cambio('personal', 'UPDATE', $cuenta->personal_id, ['foto' => $personal->imagen ? 'Sí' : 'No'], ['foto' => $nueva ? 'Actualizada' : 'Quitada']);

        return back()->with('success', $nueva ? 'Tu foto de perfil se actualizó.' : 'Se quitó tu foto de perfil.');
    }

    public function cambiarClave(Request $request)
    {
        $cuenta = $request->user();

        $datos = $request->validate([
            'clave_actual' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($datos['clave_actual'], $cuenta->clave)) {
            throw ValidationException::withMessages(['clave_actual' => 'La contraseña actual no es correcta.']);
        }

        $cuenta->clave = $datos['password'];
        $cuenta->save();

        return back()->with('success', 'Tu contraseña se cambió correctamente.');
    }
}
