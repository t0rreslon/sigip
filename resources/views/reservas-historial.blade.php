@extends('layouts.sigip')

@section('titulo', 'Historial de reservas')
@section('descripcion', 'Todas las reservas registradas por el personal y las solicitadas por los instructores.')

@section('acciones')
<a href="{{ route('reservas.index') }}" class="btn-borde">← Reservas</a>
<a href="{{ route('reservas.exportar', request()->query()) }}" class="btn-exportar">Exportar PDF</a>
@endsection

@section('contenido')
<div class="resumen">
  @forelse ($conteo as $estado => $total)
  <span class="chip {{ match ($estado) { 'Confirmada', 'En curso', 'Finalizada' => 'verde', 'Pendiente' => 'amarillo', default => 'rojo' } }}">{{ $total }} {{ mb_strtolower($estado) }}</span>
  @empty
  <span class="resumen-texto">Sin reservas en el periodo</span>
  @endforelse
</div>

<section class="tarjeta">
  <form class="filtros" method="GET" action="{{ route('reservas.historial') }}">
    <label>Desde <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"></label>
    <label>Hasta <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"></label>
    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Documento, nombre o espacio">
    <select name="espacio">
      <option value="">Espacio: todos</option>
      @foreach ($espacios as $espacio)
      <option value="{{ $espacio->espacio_id }}" @selected((string) request('espacio') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
      @endforeach
    </select>
    <select name="estado_reserva">
      <option value="">Estado: todos</option>
      @foreach ($estadosReserva as $estado)
      <option value="{{ $estado->estado_reserva_id }}" @selected((string) request('estado_reserva') === (string) $estado->estado_reserva_id)>{{ $estado->nombre_estado }}</option>
      @endforeach
    </select>
    <select name="tipo">
      <option value="">Para: todos</option>
      <option value="usuario" @selected(request('tipo') === 'usuario')>Usuarios</option>
      <option value="instructor" @selected(request('tipo') === 'instructor')>Instructores</option>
    </select>
    <button type="submit" class="btn-borde">Filtrar</button>
  </form>

  <table class="tabla">
    <thead>
      <tr><th>Espacio</th><th>Reservado para</th><th>Fecha</th><th>Horario</th><th>Estado</th><th>Creada</th></tr>
    </thead>
    <tbody>
      @forelse ($reservas as $reserva)
      <tr>
        <td>{{ $reserva->nombre_espacio }}</td>
        <td>{{ $reserva->nombre_destinatario }}<br><small>{{ $reserva->tipo_destinatario }} · {{ $reserva->documento_destinatario }}</small></td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('d/m/Y') }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('h:i a') }} – {{ \Illuminate\Support\Carbon::parse($reserva->fecha_fin)->format('h:i a') }}</td>
        <td><span class="badge {{ match ($reserva->nombre_estado) { 'Confirmada', 'En curso', 'Finalizada' => 'verde', 'Pendiente' => 'amarillo', default => 'rojo' } }}">{{ $reserva->nombre_estado }}</span></td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_creacion)->format('d/m/Y h:i a') }}</td>
      </tr>
      @empty
      <tr><td colspan="6">No hay reservas para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$reservas" etiqueta="reservas" />
</section>
@endsection
