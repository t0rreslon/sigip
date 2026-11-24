@extends('layouts.sigip')

@php
  $inicio = \Illuminate\Support\Carbon::parse($programacion->fecha_inicio);
  $fin = \Illuminate\Support\Carbon::parse($programacion->fecha_fin);
  $activa = in_array($programacion->nombre_estado, ['Pendiente', 'En curso'], true);
  $modificable = in_array($programacion->nombre_estado, ['Pendiente', 'Solicitada'], true) && $inicio->isFuture();
  $puedeAsistencia = !in_array($programacion->nombre_estado, ['Solicitada', 'Cancelada', 'Rechazada'], true) && $inicio->copy()->subMinutes(30)->isPast();
  $esAdmin = auth()->user()->tieneTipo('Administrador');
@endphp

@section('titulo', $programacion->nombre_actividad)
@section('descripcion', ucfirst($inicio->locale('es')->isoFormat('dddd D [de] MMMM YYYY')) . ' · ' . $inicio->format('h:i a') . ' – ' . $fin->format('h:i a') . ' · ' . $programacion->nombre_espacio)

@section('acciones')
<a href="{{ $puedeGestionar ? route('actividades.index') : route('portal.horario') }}" class="btn-borde">← Volver</a>
@if ($esAdmin && $modificable)
<button type="button" class="btn-borde" data-abrir="modal-programar">Modificar</button>
<form method="POST" action="{{ route('programaciones.cancelar', $programacion->programacion_actividad_id) }}" data-confirmar="¿Cancelar esta actividad? El espacio quedará libre en ese horario.">
  @csrf
  @method('PATCH')
  <button type="submit" class="btn-borde">Cancelar actividad</button>
</form>
@endif
@endsection

@section('contenido')
{{-- HU-182: disponibilidad de la actividad --}}
<section class="tarjeta">
  <dl class="detalle-grid">
    <div><dt>Estado</dt><dd>{{ $programacion->nombre_estado === 'Pendiente' ? 'Programada' : $programacion->nombre_estado }}</dd></div>
    <div><dt>Instructor</dt><dd>{{ $programacion->instructor }}</dd></div>
    <div><dt>Espacio</dt><dd>{{ $programacion->nombre_espacio }}</dd></div>
    <div><dt>Inscritos</dt><dd>{{ $inscritos->count() }}</dd></div>
    <div><dt>Cupos disponibles</dt><dd class="cifra">{{ $cupos === null ? 'Sin límite' : $cupos }}</dd></div>
    <div><dt>Asistentes registrados</dt><dd>{{ count($asistentes) }}</dd></div>
    <div><dt>Inscripción previa</dt><dd>{{ $programacion->registro_obligatorio ? 'Obligatoria' : 'No obligatoria' }}</dd></div>
  </dl>
</section>

{{-- HU-181 / HU-180 / HU-184: inscritos y asistencia --}}
<section class="tarjeta">
  <h2>Participantes</h2>

  @if ($puedeGestionar && $activa && $fin->isFuture())
  {{-- HU-183: inscribir usuario --}}
  <form class="filtros" method="POST" action="{{ route('programaciones.inscribir', $programacion->programacion_actividad_id) }}">
    @csrf
    <input type="text" name="documento" inputmode="numeric" maxlength="12" placeholder="Documento del usuario" required @disabled($cupos === 0)>
    <button type="submit" class="btn-primario" @disabled($cupos === 0)>Inscribir</button>
    @if ($cupos === 0)
    <span class="badge rojo">Sin cupos</span>
    @endif
  </form>
  @endif

  <form method="POST" action="{{ route('programaciones.asistencia', $programacion->programacion_actividad_id) }}">
    @csrf
    <table class="tabla">
      <thead>
        <tr><th>Asistió</th><th>Participante</th><th>Documento</th>@if ($puedeGestionar && $activa)<th>Acciones</th>@endif</tr>
      </thead>
      <tbody>
        @forelse ($inscritos as $inscrito)
        <tr>
          <td><input type="checkbox" name="asistentes[]" value="{{ $inscrito->usuario_id }}" @checked(in_array((int) $inscrito->usuario_id, $asistentes, true)) @disabled(!$puedeAsistencia)></td>
          <td>{{ $inscrito->nombre }} {{ $inscrito->apellido }}</td>
          <td>{{ $inscrito->documento }}</td>
          @if ($puedeGestionar && $activa)
          <td>
            <button type="submit" form="retirar-{{ $inscrito->usuario_id }}" class="btn-borde">Retirar</button>
          </td>
          @endif
        </tr>
        @empty
        <tr><td colspan="4">No hay usuarios inscritos.</td></tr>
        @endforelse
        @foreach ($asistentesLibres as $libre)
        <tr>
          <td>✔</td>
          <td>{{ $libre->nombre }} {{ $libre->apellido }} <small>(sin inscripción previa)</small></td>
          <td>{{ $libre->documento }}</td>
          @if ($puedeGestionar && $activa)<td></td>@endif
        </tr>
        @endforeach
      </tbody>
    </table>

    @if ($puedeAsistencia)
    @if (!$programacion->registro_obligatorio)
    <label class="campo-plan"><span>Agregar asistente sin inscripción (documento)</span>
      <input type="text" name="documento_libre" inputmode="numeric" maxlength="12">
    </label>
    @endif
    <div class="modal-plan-acciones">
      <button type="submit" class="btn-primario">{{ count($asistentes) > 0 ? 'Corregir asistencia' : 'Registrar asistencia' }}</button>
    </div>
    @else
    <p class="nota-plan">La asistencia se registra desde 30 minutos antes del inicio de la actividad programada.</p>
    @endif
  </form>

  @foreach ($inscritos as $inscrito)
  <form id="retirar-{{ $inscrito->usuario_id }}" method="POST" action="{{ route('programaciones.desinscribir', [$programacion->programacion_actividad_id, $inscrito->usuario_id]) }}" data-confirmar="¿Retirar a {{ $inscrito->nombre }} de la actividad?">
    @csrf
    @method('DELETE')
  </form>
  @endforeach
</section>

<section class="tarjeta">
  <h2>Cambios registrados</h2>
  <x-historial-cambios :registros="$historial" />
</section>
@endsection

@if ($esAdmin && $modificable)
@push('modales')
@include('actividades._modal-programar', ['programacion' => $programacion])
@endpush
@endif
