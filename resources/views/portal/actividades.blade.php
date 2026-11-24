@extends('layouts.sigip')

@section('titulo', 'Mis actividades solicitadas')
@section('descripcion', 'Propón actividades para dictar. El administrador las valida antes de programarlas.')

@section('acciones')
<a href="{{ route('portal.horario') }}" class="btn-borde">Mi horario</a>
<button type="button" class="btn-primario" data-abrir="modal-solicitud" data-accion="{{ route('portal.actividades.store') }}"
  data-campos='{"_method":"POST","actividad_id":"","espacio_id":"","fecha":"{{ date('Y-m-d') }}","hora_inicio":"","hora_fin":"","registro_obligatorio":false}'
  data-textos='{"titulo-modal-solicitud":"Solicitar actividad"}'>+ Solicitar actividad</button>
@endsection

@section('contenido')
{{-- HU-203 --}}
<section class="tarjeta">
  <table class="tabla">
    <thead>
      <tr><th>Actividad</th><th>Fecha</th><th>Horario</th><th>Espacio</th><th>Inscritos</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      @forelse ($solicitudes as $solicitud)
      @php
        $inicio = \Illuminate\Support\Carbon::parse($solicitud->fecha_inicio);
        $fin = \Illuminate\Support\Carbon::parse($solicitud->fecha_fin);
        $editable = in_array($solicitud->nombre_estado, ['Solicitada', 'Pendiente'], true) && $inicio->isFuture();
      @endphp
      <tr>
        <td>{{ $solicitud->nombre_actividad }}</td>
        <td>{{ $inicio->format('d/m/Y') }}</td>
        <td>{{ $inicio->format('h:i a') }} – {{ $fin->format('h:i a') }}</td>
        <td>{{ $solicitud->nombre_espacio }}</td>
        <td>{{ $solicitud->inscritos }}</td>
        <td><span class="badge {{ match ($solicitud->nombre_estado) { 'Pendiente', 'En curso', 'Finalizada' => 'verde', 'Solicitada' => 'amarillo', default => 'rojo' } }}">{{ $solicitud->nombre_estado === 'Pendiente' ? 'Aprobada' : ($solicitud->nombre_estado === 'Solicitada' ? 'Por validar' : $solicitud->nombre_estado) }}</span></td>
        <td>
          <div class="acciones-fila">
            @if (!in_array($solicitud->nombre_estado, ['Solicitada', 'Rechazada', 'Cancelada'], true))
            <a href="{{ route('programaciones.show', $solicitud->programacion_actividad_id) }}" class="btn-borde">Asistencia</a>
            @endif
            @if ($editable)
            {{-- HU-202 --}}
            <button type="button" class="btn-borde" data-abrir="modal-solicitud" data-accion="{{ route('portal.actividades.update', $solicitud->programacion_actividad_id) }}"
              data-campos='{{ json_encode(["_method" => "PUT", "actividad_id" => $solicitud->actividad_id, "espacio_id" => $solicitud->espacio_id, "fecha" => $inicio->format("Y-m-d"), "hora_inicio" => $inicio->format("H:i"), "hora_fin" => $fin->format("H:i"), "registro_obligatorio" => (bool) $solicitud->registro_obligatorio]) }}'
              data-textos='{"titulo-modal-solicitud":"Modificar actividad"}'>Modificar</button>
            <form method="POST" action="{{ route('portal.actividades.cancelar', $solicitud->programacion_actividad_id) }}" data-confirmar="¿Cancelar esta actividad?">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn-borde">Cancelar</button>
            </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="7">Aún no has solicitado actividades.</td></tr>
      @endforelse
    </tbody>
  </table>
  <x-paginacion :p="$solicitudes" etiqueta="actividades" />
</section>
@endsection

@push('modales')
{{-- HU-200 --}}
<dialog class="modal-plan" id="modal-solicitud">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-modal-solicitud">Solicitar actividad</h2>
        <p>Se verifica que el espacio y tu horario estén libres ({{ $horaApertura }} a {{ $horaCierre }}).</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ old('_accion', route('portal.actividades.store')) }}">
      @csrf
      <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
      <label class="campo-plan"><span>Actividad *</span>
        <select name="actividad_id" required>
          <option value="">Selecciona</option>
          @foreach ($actividades as $actividad)
          <option value="{{ $actividad->actividad_id }}" @selected((string) old('actividad_id') === (string) $actividad->actividad_id)>{{ $actividad->nombre_actividad }}</option>
          @endforeach
        </select>
      </label>
      <label class="campo-plan"><span>Espacio *</span>
        <select name="espacio_id" required>
          <option value="">Selecciona</option>
          @foreach ($espacios as $espacio)
          <option value="{{ $espacio->espacio_id }}" @selected((string) old('espacio_id') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
          @endforeach
        </select>
      </label>
      <div class="fila-plan">
        <label class="campo-plan"><span>Fecha *</span><input type="date" name="fecha" min="{{ date('Y-m-d') }}" value="{{ old('fecha', date('Y-m-d')) }}" required></label>
        <label class="campo-plan"><span>Desde *</span><input type="time" name="hora_inicio" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_inicio') }}" required></label>
        <label class="campo-plan"><span>Hasta *</span><input type="time" name="hora_fin" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_fin') }}" required></label>
      </div>
      <input type="hidden" name="registro_obligatorio" value="0">
      <label><input type="checkbox" name="registro_obligatorio" value="1" @checked(old('registro_obligatorio'))> Exige inscripción previa</label>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Enviar</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
