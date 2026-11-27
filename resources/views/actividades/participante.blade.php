@extends('layouts.sigip')

@section('titulo', 'Participación y asistencia')
@section('descripcion', 'Actividades en las que un participante se inscribió y a cuáles asistió.')

@section('acciones')
<a href="{{ route('actividades.index') }}" class="btn-borde">← Actividades</a>
@endsection

@section('contenido')
<nav class="pestanas" aria-label="Participación y asistencia">
  <a href="{{ route('actividades.reporte') }}" class="">Por actividad</a>
  <a href="{{ route('actividades.participante') }}" class="activa">Por participante</a>
</nav>

<section class="tarjeta">
  <form class="filtros" method="GET" action="{{ route('actividades.participante') }}">
    <input type="text" name="documento" inputmode="numeric" maxlength="12" value="{{ request('documento') }}" placeholder="Documento del participante" required>
    <button type="submit" class="btn-primario">Consultar</button>
  </form>

  @if (request()->filled('documento') && !$usuario)
  <p class="mensaje-error">No existe un usuario con ese documento.</p>
  @endif

  @if ($usuario)
  @php
    $pasadas = $registros->filter(fn ($r) => \Illuminate\Support\Carbon::parse($r->fecha_fin)->isPast() && $r->inscrito);
    $asistidas = $registros->where('asistio', true)->count();
  @endphp
  <dl class="detalle-grid">
    <div><dt>Participante</dt><dd>{{ $usuario->nombre }} {{ $usuario->apellido }}</dd></div>
    <div><dt>Documento</dt><dd>{{ $usuario->documento }}</dd></div>
    <div><dt>Actividades registradas</dt><dd>{{ $registros->count() }}</dd></div>
    <div><dt>Asistencias</dt><dd>{{ $asistidas }}</dd></div>
    <div><dt>Cumplimiento</dt><dd>{{ $pasadas->count() > 0 ? round($pasadas->where('asistio', true)->count() / $pasadas->count() * 100) . ' %' : '—' }}</dd></div>
  </dl>

  <table class="tabla">
    <thead>
      <tr><th>Actividad</th><th>Fecha</th><th>Instructor</th><th>Espacio</th><th>Inscrito</th><th>Asistió</th></tr>
    </thead>
    <tbody>
      @forelse ($registros as $registro)
      @php($inicio = \Illuminate\Support\Carbon::parse($registro->fecha_inicio))
      <tr>
        <td><a href="{{ route('programaciones.show', $registro->programacion_actividad_id) }}">{{ $registro->nombre_actividad }}</a></td>
        <td>{{ $inicio->format('d/m/Y h:i a') }}</td>
        <td>{{ $registro->instructor }}</td>
        <td>{{ $registro->nombre_espacio }}</td>
        <td>{{ $registro->inscrito ? 'Sí' : 'No' }}</td>
        <td>
          @if ($registro->asistio)
          <span class="badge verde">Sí</span>
          @elseif ($inicio->isFuture())
          <span class="badge gris">Pendiente</span>
          @else
          <span class="badge rojo">No</span>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="6">El participante no tiene actividades registradas.</td></tr>
      @endforelse
    </tbody>
  </table>
  @endif
</section>
@endsection
