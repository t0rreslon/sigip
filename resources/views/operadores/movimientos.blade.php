@extends('layouts.sigip')

@section('titulo', 'Historial de acciones de los operadores')
@section('descripcion', 'Registros, modificaciones y exportaciones hechas por cada operador.')

@section('acciones')
<a href="{{ route('operadores.index') }}" class="btn-borde">← Operadores</a>
@endsection

@section('contenido')
<section class="tarjeta">
  <form class="filtros" method="GET" action="{{ route('operadores.movimientos') }}">
    <label>Desde <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"></label>
    <label>Hasta <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"></label>
    <select name="cuenta">
      <option value="">Operador: todos</option>
      @foreach ($operadores as $operador)
      <option value="{{ $operador->cuenta_id }}" @selected((string) request('cuenta') === (string) $operador->cuenta_id)>{{ $operador->login }}</option>
      @endforeach
    </select>
    <select name="accion">
      <option value="">Acción: todas</option>
      @foreach ($acciones as $accion)
      <option value="{{ $accion->tipo_accion_id }}" @selected((string) request('accion') === (string) $accion->tipo_accion_id)>{{ $accion->nombre_accion }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-borde">Filtrar</button>
  </form>

  <table class="tabla">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Operador</th>
        <th>Acción</th>
        <th>Detalle</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($movimientos as $movimiento)
      <tr>
        <td>{{ \Illuminate\Support\Carbon::parse($movimiento->fecha_hora)->format('d/m/Y h:i:s a') }}</td>
        <td>{{ $movimiento->nombre }} {{ $movimiento->apellido }}<br><small>{{ $movimiento->login }}</small></td>
        <td><span class="badge morado">{{ $movimiento->nombre_accion }}</span></td>
        <td>
          @if (!empty($movimiento->detalle['ruta']))
          {{ \App\Support\NombresRutas::describir($movimiento->detalle['ruta']) }}
          @elseif (!empty($movimiento->detalle['reporte']))
          Reporte PDF de {{ $movimiento->detalle['reporte'] }}
          @else
          —
          @endif
          @if (!empty($movimiento->detalle['parametros']))
          <br><small>Registro #{{ implode(', #', array_map('strval', $movimiento->detalle['parametros'])) }}</small>
          @endif
        </td>
        <td>{{ $movimiento->ip }}</td>
      </tr>
      @empty
      <tr><td colspan="5">No hay acciones registradas para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$movimientos" etiqueta="acciones" />
</section>
@endsection
