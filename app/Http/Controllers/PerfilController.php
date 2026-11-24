<?php

namespace App\Http\Controllers;

use App\Support\Auditoria;
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

    public function cambiarClave(Request $request)
    {
        $cuenta = $request->user();

        $datos = $request->validate([
            'clave_actual' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (!Hash::check($datos['clave_actual'], $cuenta->clave)) {
            throw ValidationException::withMessages(['clave_actual' => 'La contraseña actual no es correcta.']);
        }

        $cuenta->clave = $datos['password'];
        $cuenta->save();

        return back()->with('success', 'Tu contraseña se cambió correctamente.');
    }
}
