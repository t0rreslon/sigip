@extends('layouts.sigip')

@section('titulo', 'Préstamo #' . $prestamo->prestamo_id)
@section('descripcion', 'Datos del préstamo, del destinatario y del estado de cada implemento.')

@section('acciones')
<a href="{{ route('prestamos') }}" class="btn-borde">← Préstamos</a>
@endsection

@section('contenido')
@php
  $prestado = \Illuminate\Support\Carbon::parse($prestamo->fecha_prestado);
  $devuelto = $prestamo->fecha_devuelto ? \Illuminate\Support\Carbon::parse($prestamo->fecha_devuelto) : null;
@endphp
<section class="tarjeta">
  <h2>Datos del préstamo</h2>
  <dl class="detalle-grid">
    <div><dt>Estado</dt><dd><span class="badge {{ $prestamo->activo ? 'amarillo' : 'verde' }}">{{ $prestamo->activo ? 'Activo' : 'Finalizado' }}</span></dd></div>
    <div><dt>Fecha de préstamo</dt><dd>{{ $prestado->format('d/m/Y h:i a') }}</dd></div>
    <div><dt>Fecha de devolución</dt><dd>{{ $devuelto?->format('d/m/Y h:i a') ?? 'Pendiente' }}</dd></div>
    <div><dt>Duración</dt><dd>{{ $prestado->diffForHumans($devuelto ?? now(), true) }}</dd></div>
    <div><dt>Destinatario</dt><dd>{{ $prestamo->destinatario }}</dd></div>
    <div><dt>Tipo</dt><dd>{{ $prestamo->tipo_destinatario }}</dd></div>
    <div><dt>Documento</dt><dd>{{ $prestamo->documento }}</dd></div>
    <div><dt>Contacto</dt><dd>{{ $prestamo->telefono ?? $prestamo->correo ?? 'Sin datos' }}</dd></div>
  </dl>
</section>

<section class="tarjeta">
  <h2>Implementos ({{ $implementos->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr>
        <th>Implemento</th>
        <th>Categoría</th>
        <th>Estado de devolución</th>
        <th>Estado actual</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($implementos as $implemento)
      <tr>
        <td><a href="{{ route('inventario.show', ['implemento', $implemento->implemento_id]) }}">IMP-{{ $implemento->implemento_id }} · {{ $implemento->nombre_implemento }}</a></td>
        <td>{{ $implemento->nombre_categoria ?? '—' }}</td>
        <td><span class="badge {{ $implemento->estado_devolucion === 'Pendiente' ? 'amarillo' : ($implemento->estado_devolucion === 'Intacto' ? 'verde' : 'rojo') }}">{{ $implemento->estado_devolucion }}</span></td>
        <td>{{ $implemento->estado_actual }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</section>
@endsection
