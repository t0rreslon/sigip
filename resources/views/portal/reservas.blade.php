@extends('layouts.sigip')

@section('titulo', 'Mis reservas')
@section('descripcion', 'Solicita espacios para tus clases. El personal del gimnasio confirma cada solicitud.')

@section('acciones')
<button type="button" class="btn-primario" data-abrir="modal-reserva" data-accion="{{ route('portal.reservas.store') }}"
  data-campos='{"_method":"POST","espacio_id":"","fecha":"{{ $fecha->format('Y-m-d') }}","hora_inicio":"","hora_fin":""}'
  data-textos='{"titulo-modal-reserva":"Solicitar reserva"}'>+ Solicitar reserva</button>
@endsection

@section('contenido')
{{-- HU-173: mis reservas --}}
<section class="tarjeta">
  <h2>Mis reservas</h2>
  <table class="tabla">
    <thead>
      <tr><th>Espacio</th><th>Fecha</th><th>Horario</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      @forelse ($reservas as $reserva)
      @php
        $inicio = \Illuminate\Support\Carbon::parse($reserva->fecha_inicio);
        $fin = \Illuminate\Support\Carbon::parse($reserva->fecha_fin);
        $editable = in_array($reserva->nombre_estado, ['Pendiente', 'Confirmada'], true) && $fin->isFuture();
      @endphp
      <tr>
        <td>{{ $reserva->nombre_espacio }}</td>
        <td>{{ $inicio->format('d/m/Y') }}</td>
        <td>{{ $inicio->format('h:i a') }} – {{ $fin->format('h:i a') }}</td>
        <td><span class="badge {{ match ($reserva->nombre_estado) { 'Confirmada', 'En curso', 'Finalizada' => 'verde', 'Pendiente' => 'amarillo', default => 'rojo' } }}">{{ $reserva->nombre_estado === 'Pendiente' ? 'Por confirmar' : $reserva->nombre_estado }}</span></td>
        <td>
          @if ($editable)
          <div class="acciones-fila">
            {{-- HU-171 --}}
            <button type="button" class="btn-borde" data-abrir="modal-reserva" data-accion="{{ route('portal.reservas.update', $reserva->reserva_id) }}"
              data-campos='{{ json_encode(["_method" => "PUT", "espacio_id" => $reserva->espacio_id, "fecha" => $inicio->format("Y-m-d"), "hora_inicio" => $inicio->format("H:i"), "hora_fin" => $fin->format("H:i")]) }}'
              data-textos='{"titulo-modal-reserva":"Modificar reserva"}'>Modificar</button>
            {{-- HU-172 --}}
            <form method="POST" action="{{ route('portal.reservas.cancelar', $reserva->reserva_id) }}" data-confirmar="¿Cancelar esta reserva?">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn-borde">Cancelar</button>
            </form>
          </div>
          @else
          —
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="5">Aún no tienes reservas.</td></tr>
      @endforelse
    </tbody>
  </table>
  <x-paginacion :p="$reservas" etiqueta="reservas" />
</section>

{{-- HU-48: disponibilidad para elegir horario --}}
<section class="tarjeta">
  <h2>Disponibilidad de espacios</h2>
  <form class="filtros" method="GET" action="{{ route('portal.reservas') }}">
    <label>Fecha <input type="date" name="fecha" min="{{ date('Y-m-d') }}" value="{{ $fecha->format('Y-m-d') }}"></label>
    <label>Desde <input type="time" name="hora_inicio" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ request('hora_inicio') }}"></label>
    <label>Hasta <input type="time" name="hora_fin" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ request('hora_fin') }}"></label>
    <button type="submit" class="btn-borde">Consultar</button>
  </form>

  <div class="grilla-espacios">
    @foreach ($espacios as $espacio)
    <article class="tarjeta-espacio {{ $espacio->libre_en_rango === true ? 'libre' : ($espacio->libre_en_rango === false ? 'ocupado' : '') }}">
      <h3>{{ $espacio->nombre_espacio }}</h3>
      <span class="badge {{ $espacio->habilitado ? 'verde' : 'rojo' }}">{{ $espacio->habilitado ? 'Habilitado' : $espacio->nombre_estado }}</span>
      <ul>
        @forelse ($espacio->reservas as $ocupacion)
        <li>{{ \Illuminate\Support\Carbon::parse($ocupacion->fecha_inicio)->format('h:i a') }} – {{ \Illuminate\Support\Carbon::parse($ocupacion->fecha_fin)->format('h:i a') }} · ocupado</li>
        @empty
        <li>Libre todo el día.</li>
        @endforelse
      </ul>
    </article>
    @endforeach
  </div>
</section>
@endsection

@push('modales')
<dialog class="modal-plan" id="modal-reserva">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-modal-reserva">Solicitar reserva</h2>
        <p>Horario de atención: {{ $horaApertura }} a {{ $horaCierre }}</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ old('_accion', route('portal.reservas.store')) }}">
      @csrf
      <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
      <label class="campo-plan"><span>Espacio *</span>
        <select name="espacio_id" required>
          <option value="">Selecciona</option>
          @foreach ($espacios->where('habilitado', true) as $espacio)
          <option value="{{ $espacio->espacio_id }}" @selected((string) old('espacio_id') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
          @endforeach
        </select>
      </label>
      <div class="fila-plan">
        <label class="campo-plan"><span>Fecha *</span><input type="date" name="fecha" min="{{ date('Y-m-d') }}" value="{{ old('fecha', $fecha->format('Y-m-d')) }}" required></label>
        <label class="campo-plan"><span>Desde *</span><input type="time" name="hora_inicio" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_inicio') }}" required></label>
        <label class="campo-plan"><span>Hasta *</span><input type="time" name="hora_fin" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_fin') }}" required></label>
      </div>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Enviar</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
