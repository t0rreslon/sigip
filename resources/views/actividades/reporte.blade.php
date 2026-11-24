@extends('layouts.sigip')

@section('titulo', 'Participación y asistencia')
@section('descripcion', 'Sesiones, inscripciones y asistencias por actividad en el periodo seleccionado.')

@section('acciones')
<a href="{{ route('actividades.index') }}" class="btn-borde">← Actividades</a>
<a href="{{ route('actividades.reporte.exportar', request()->query()) }}" class="btn-exportar">Exportar PDF</a>
@endsection

@section('contenido')
<div class="resumen">
  <span class="chip morado">{{ $filas->sum('sesiones') }} sesiones</span>
  <span class="chip verde">{{ $filas->sum('asistencias') }} asistencias</span>
  <span class="chip amarillo">{{ $filas->sum('inscripciones') }} inscripciones</span>
  <span class="resumen-texto">{{ $desde->format('d/m/Y') }} – {{ $hasta->format('d/m/Y') }}</span>
</div>

<section class="tarjeta">
  <form class="filtros" method="GET" action="{{ route('actividades.reporte') }}">
    <label>Desde <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"></label>
    <label>Hasta <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"></label>
    <select name="actividad">
      <option value="">Actividad: todas</option>
      @foreach ($actividades as $actividad)
      <option value="{{ $actividad->actividad_id }}" @selected((string) request('actividad') === (string) $actividad->actividad_id)>{{ $actividad->nombre_actividad }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-borde">Consultar</button>
  </form>

  <table class="tabla">
    <thead>
      <tr><th>Actividad</th><th>Sesiones</th><th>Canceladas</th><th>Inscripciones</th><th>Asistencias</th><th>Personas distintas</th><th>% asistencia</th></tr>
    </thead>
    <tbody>
      @forelse ($filas as $fila)
      <tr>
        <td>{{ $fila->nombre_actividad }}</td>
        <td>{{ $fila->sesiones }}</td>
        <td>{{ $fila->canceladas }}</td>
        <td>{{ $fila->inscripciones }}</td>
        <td>{{ $fila->asistencias }}</td>
        <td>{{ $fila->personas }}</td>
        <td>{{ $fila->inscripciones > 0 ? round($fila->asistencias / $fila->inscripciones * 100) . ' %' : '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="7">No hay actividades en el periodo seleccionado.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>
@endsection
