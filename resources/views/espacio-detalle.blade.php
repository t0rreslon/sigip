@extends('layouts.sigip')

@section('titulo', $espacio->nombre_espacio)
@section('descripcion', 'Información, reservas, actividades, equipos y mantenimientos del espacio.')

@section('acciones')
<a href="{{ route('espacios.index') }}" class="btn-borde">← Espacios</a>
<a href="{{ route('reservas.index', ['espacio' => $espacio->espacio_id]) }}" class="btn-borde">Disponibilidad</a>
@if (auth()->user()->tieneTipo('Administrador'))
<button type="button" class="btn-primario" data-abrir="modal-mantenimiento">+ Registrar mantenimiento</button>
@endif
@endsection

@section('contenido')
<section class="tarjeta">
  <h2>Datos del espacio</h2>
  <dl class="detalle-grid">
    <div><dt>Estado</dt><dd><span class="badge {{ match ($espacio->nombre_estado) { 'Disponible' => 'verde', 'Reservado' => 'amarillo', default => 'rojo' } }}">{{ $espacio->nombre_estado }}</span></dd></div>
    <div><dt>Capacidad</dt><dd>{{ $espacio->capacidad ? $espacio->capacidad . ' personas' : 'Sin definir' }}</dd></div>
    <div><dt>Máquinas ubicadas</dt><dd>{{ $maquinas->count() }}</dd></div>
    <div><dt>Próximas reservas</dt><dd>{{ $reservas->count() }}</dd></div>
    <div><dt>Próximas actividades</dt><dd>{{ $actividades->count() }}</dd></div>
    <div><dt>Descripción</dt><dd>{{ $espacio->descripcion ?: 'Sin descripción' }}</dd></div>
  </dl>
</section>

<section class="tarjeta">
  <h2>Próximas reservas y actividades</h2>
  <table class="tabla">
    <thead>
      <tr><th>Tipo</th><th>Fecha</th><th>Horario</th><th>Detalle</th><th>Estado</th></tr>
    </thead>
    <tbody>
      @forelse ($reservas as $reserva)
      <tr>
        <td>Reserva</td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('d/m/Y') }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('h:i a') }} – {{ \Illuminate\Support\Carbon::parse($reserva->fecha_fin)->format('h:i a') }}</td>
        <td>{{ $reserva->destinatario }}</td>
        <td>{{ $reserva->nombre_estado }}</td>
      </tr>
      @empty
      @endforelse
      @foreach ($actividades as $actividad)
      <tr>
        <td>Actividad</td>
        <td>{{ \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y') }}</td>
        <td>{{ \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('h:i a') }} – {{ \Illuminate\Support\Carbon::parse($actividad->fecha_fin)->format('h:i a') }}</td>
        <td>{{ $actividad->nombre_actividad }} · {{ $actividad->instructor }}</td>
        <td>{{ $actividad->nombre_estado }}</td>
      </tr>
      @endforeach
      @if ($reservas->isEmpty() && $actividades->isEmpty())
      <tr><td colspan="5">No hay reservas ni actividades próximas.</td></tr>
      @endif
    </tbody>
  </table>
</section>

<section class="tarjeta">
  <h2>Mantenimientos ({{ $mantenimientos->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr><th>Desde</th><th>Hasta</th><th>Detalle</th><th>Registró</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      @forelse ($mantenimientos as $mantenimiento)
      <tr>
        <td>{{ \Illuminate\Support\Carbon::parse($mantenimiento->fecha_inicio)->format('d/m/Y') }}</td>
        <td>{{ $mantenimiento->fecha_fin ? \Illuminate\Support\Carbon::parse($mantenimiento->fecha_fin)->format('d/m/Y') : 'Sin definir' }}</td>
        <td>{{ $mantenimiento->detalles }}</td>
        <td>{{ $mantenimiento->registrado_por ?? '—' }}</td>
        <td><span class="badge {{ $mantenimiento->nombre_estado === 'Finalizado' ? 'verde' : 'amarillo' }}">{{ $mantenimiento->nombre_estado }}</span></td>
        <td>
          @if ($mantenimiento->nombre_estado !== 'Finalizado' && auth()->user()->tieneTipo('Administrador'))
          <form method="POST" action="{{ route('espacios.mantenimientos.finalizar', [$espacio->espacio_id, $mantenimiento->mantenimiento_espacio_id]) }}" data-confirmar="¿Marcar este mantenimiento como finalizado?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn-borde">Finalizar</button>
          </form>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="6">No hay mantenimientos registrados.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>

<section class="tarjeta">
  <h2>Máquinas en este espacio</h2>
  <table class="tabla">
    <thead><tr><th>Máquina</th><th>Estado</th></tr></thead>
    <tbody>
      @forelse ($maquinas as $maquina)
      <tr>
        <td><a href="{{ route('inventario.show', ['maquina', $maquina->maquina_id]) }}">MAQ-{{ $maquina->maquina_id }} · {{ $maquina->nombre_maquina }}</a></td>
        <td>{{ $maquina->nombre_estado }}</td>
      </tr>
      @empty
      <tr><td colspan="2">No hay máquinas ubicadas en este espacio.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>

<section class="tarjeta">
  <h2>Cambios registrados</h2>
  <x-historial-cambios :registros="$historial" />
</section>
@endsection

@push('modales')
{{-- HU-168 --}}
<dialog class="modal-plan" id="modal-mantenimiento">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2>Registrar mantenimiento</h2>
        <p>{{ $espacio->nombre_espacio }}</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ route('espacios.mantenimientos.store', $espacio->espacio_id) }}">
      @csrf
      <input type="hidden" name="_modal" value="modal-mantenimiento">
      <div class="fila-plan">
        <label class="campo-plan"><span>Desde *</span><input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', date('Y-m-d')) }}" required></label>
        <label class="campo-plan"><span>Hasta</span><input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}"></label>
      </div>
      <label class="campo-plan"><span>Detalle *</span><input type="text" name="detalles" maxlength="2000" value="{{ old('detalles') }}" placeholder="Ej. Pintura y cambio de piso" required></label>
      <label class="campo-plan"><span>Estado *</span>
        <select name="estado_maquina_mantenimiento_id" required>
          @foreach ($estadosMantenimiento as $estado)
          <option value="{{ $estado->estado_maquina_mantenimiento_id }}">{{ $estado->nombre_estado }}</option>
          @endforeach
        </select>
      </label>
      <label><input type="checkbox" name="bloquear" value="1" @checked(old('bloquear', true))> Poner el espacio en "Mantenimiento" (no se podrá reservar)</label>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Registrar</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
