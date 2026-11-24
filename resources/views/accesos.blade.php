@extends('layouts.sigip')

@section('titulo', 'Historial de accesos')
@section('descripcion', 'Consulta los ingresos y salidas registrados por periodo, usuario o estado.')

@section('acciones')
<a href="{{ route('control-acceso.index') }}" class="btn-borde">Control de acceso</a>
<a href="{{ route('control-acceso.exportar', request()->query()) }}" class="btn-exportar">Exportar PDF</a>
@endsection

@section('contenido')
{{-- HU-156: cantidad de ingresos en el periodo --}}
<div class="resumen">
  <span class="chip morado">{{ (int) $totales->ingresos }} ingresos</span>
  <span class="chip verde">{{ (int) $totales->usuarios }} usuarios distintos</span>
  <span class="chip amarillo">{{ (int) $totales->sin_salida }} sin salida en el periodo</span>
  <span class="resumen-texto">{{ $desde->format('d/m/Y') }} – {{ $hasta->format('d/m/Y') }}</span>
</div>

<section class="tarjeta">
  {{-- HU-153: filtros --}}
  <form class="filtros" method="GET" action="{{ route('control-acceso.historial') }}">
    @if ($usuarioSeleccionado)
    <input type="hidden" name="usuario_id" value="{{ $usuarioSeleccionado->usuario_id }}">
    @endif
    <label>Desde
      <input type="date" name="fecha_desde" value="{{ $desde->format('Y-m-d') }}">
    </label>
    <label>Hasta
      <input type="date" name="fecha_hasta" value="{{ $hasta->format('Y-m-d') }}">
    </label>
    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Documento o nombre del usuario">
    <select name="estado">
      <option value="">Estado: todos</option>
      <option value="sin_salida" @selected(request('estado') === 'sin_salida')>Sin salida</option>
      <option value="con_salida" @selected(request('estado') === 'con_salida')>Con salida</option>
    </select>
    <button type="submit" class="btn-borde">Filtrar</button>
    @if (request()->hasAny(['buscar', 'estado', 'usuario_id', 'fecha_desde', 'fecha_hasta']))
    <a href="{{ route('control-acceso.historial') }}" class="btn-borde">Limpiar</a>
    @endif
  </form>

  @if ($usuarioSeleccionado)
  {{-- HU-155: accesos de un usuario --}}
  <p class="nota-plan">Mostrando solo los accesos de <strong>{{ $usuarioSeleccionado->nombre }} {{ $usuarioSeleccionado->apellido }}</strong> ({{ $usuarioSeleccionado->documento }}).</p>
  @endif

  {{-- HU-154: todos los registros de acceso --}}
  <table class="tabla">
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Ingreso</th>
        <th>Salida</th>
        <th>Permanencia</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($accesos as $acceso)
      @php
        $ingreso = \Illuminate\Support\Carbon::parse($acceso->fecha_ingreso);
        $salida = $acceso->fecha_salida ? \Illuminate\Support\Carbon::parse($acceso->fecha_salida) : null;
      @endphp
      <tr>
        <td>{{ $acceso->nombre }} {{ $acceso->apellido }}<br><small>{{ $acceso->documento }}</small></td>
        <td>{{ $ingreso->format('d/m/Y h:i a') }}</td>
        <td>
          @if ($salida)
          {{ $salida->format('d/m/Y h:i a') }}
          @else
          <span class="badge amarillo">Sin salida</span>
          @endif
        </td>
        <td>{{ $salida ? $ingreso->diff($salida)->format('%H h %I min') : '—' }}</td>
        <td>
          <a href="{{ route('control-acceso.historial', ['usuario_id' => $acceso->usuario_id, 'fecha_desde' => $desde->format('Y-m-d'), 'fecha_hasta' => $hasta->format('Y-m-d')]) }}" class="btn-borde">Ver accesos del usuario</a>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="5">No hay accesos para los filtros seleccionados.</td>
      </tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$accesos" etiqueta="accesos" />
</section>

{{-- HU-157: usuarios con ingreso sin salida --}}
<section class="tarjeta">
  <h2>Usuarios con ingreso sin salida ({{ $sinSalida->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Ingresó</th>
        <th>Tiempo dentro</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($sinSalida as $registro)
      <tr>
        <td>{{ $registro->nombre }} {{ $registro->apellido }}<br><small>{{ $registro->documento }}</small></td>
        <td>{{ \Illuminate\Support\Carbon::parse($registro->fecha_ingreso)->format('d/m/Y h:i a') }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($registro->fecha_ingreso)->diffForHumans(now(), true) }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="3">Todos los ingresos tienen su salida registrada.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</section>
@endsection
