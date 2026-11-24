@extends('layouts.sigip')

@section('titulo', 'Notificaciones')
@section('descripcion', 'Solicitudes de los instructores que esperan una respuesta.')

@section('contenido')
{{-- HU-199: reservas solicitadas --}}
<section class="tarjeta">
  <h2>Reservas solicitadas ({{ $reservas->count() }})</h2>
  <table class="tabla">
    <thead><tr><th>Solicitante</th><th>Espacio</th><th>Fecha y horario</th><th>Solicitada</th><th></th></tr></thead>
    <tbody>
      @forelse ($reservas as $reserva)
      <tr>
        <td>{{ $reserva->nombre_destinatario }}</td>
        <td>{{ $reserva->nombre_espacio }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('d/m/Y h:i a') }} – {{ \Illuminate\Support\Carbon::parse($reserva->fecha_fin)->format('h:i a') }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_creacion)->diffForHumans() }}</td>
        <td><a class="btn-borde" href="{{ route('reservas.index', ['fecha' => \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('Y-m-d')]) }}">Responder</a></td>
      </tr>
      @empty
      <tr><td colspan="5">No hay reservas por responder.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>

{{-- HU-204: actividades solicitadas --}}
<section class="tarjeta">
  <h2>Actividades solicitadas ({{ $actividades->count() }})</h2>
  <table class="tabla">
    <thead><tr><th>Instructor</th><th>Actividad</th><th>Espacio</th><th>Fecha y horario</th><th></th></tr></thead>
    <tbody>
      @forelse ($actividades as $actividad)
      <tr>
        <td>{{ $actividad->instructor }}</td>
        <td>{{ $actividad->nombre_actividad }}</td>
        <td>{{ $actividad->nombre_espacio }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y h:i a') }} – {{ \Illuminate\Support\Carbon::parse($actividad->fecha_fin)->format('h:i a') }}</td>
        <td><a class="btn-borde" href="{{ route('actividades.index', ['vista' => 'solicitadas']) }}">{{ auth()->user()->tieneTipo('Administrador') ? 'Validar' : 'Ver' }}</a></td>
      </tr>
      @empty
      <tr><td colspan="5">No hay actividades por validar.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>
@endsection
