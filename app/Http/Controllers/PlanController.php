<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    // Mostrar todos los planes registrados.
    public function index()
    {
        $planes = Plan::orderBy('nombre_plan')->get();

        return view('planes', compact('planes'));
    }

    // Registrar un nuevo plan.
    public function store(Request $request)
{
    $datosValidos = $request->validate([
        'nombre_plan' => [
            'required',
            'string',
            'max:30',
            'unique:plan,nombre_plan'
        ],
        'descripcion' => 'nullable|string',
        'tipo_plan' => 'required|in:tiquetera,suscripcion',
        'cantidad_entradas' => 'required|integer|min:1',
        'costo' => 'required|numeric|min:0'
    ]);

    $datosValidos['estado_plan'] = true;

    Plan::create($datosValidos);

    return redirect('/planes')->with(
        'mensaje',
        'Plan registrado exitosamente.'
    );
}

    // Mostrar los datos de un plan para editarlo.
    public function edit($id)
    {
        $plan = Plan::findOrFail($id);

        return view('editar_plan', compact('plan'));
    }
// Actualizar un plan existente.
public function update(Request $request, $id)
{
    $plan = Plan::findOrFail($id);

    $datosValidos = $request->validate([
        'nombre_plan' => [
            'required',
            'string',
            'max:30',
            Rule::unique('plan', 'nombre_plan')
                ->ignore($plan->plan_id, 'plan_id')
        ],
        'descripcion' => 'nullable|string',
        'tipo_plan' => 'required|in:tiquetera,suscripcion',
        'cantidad_entradas' => 'required|integer|min:1',
        'costo' => 'required|numeric|min:0'
    ]);

    $plan->update($datosValidos);

    $plan->ultima_actualizacion = now();
    $plan->save();

    return redirect('/planes')->with(
        'mensaje',
        'Plan actualizado exitosamente.'
    );
}

    // Desactivar un plan sin eliminarlo de la base de datos.
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);

        $plan->estado_plan = false;
        $plan->ultima_actualizacion = now();
        $plan->save();

        return redirect('/planes')->with(
            'mensaje',
            'Plan desactivado exitosamente.'
        );
    }

    // Reactivar un plan.
    public function activar($id)
    {
        $plan = Plan::findOrFail($id);

        $plan->estado_plan = true;
        $plan->ultima_actualizacion = now();
        $plan->save();

        return redirect('/planes')->with(
            'mensaje',
            'Plan reactivado exitosamente.'
        );
    }
}
