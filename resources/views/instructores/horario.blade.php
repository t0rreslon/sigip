@extends('layouts.sigip')

@section('titulo', $puedeAsignar ? 'Horario del instructor' : 'Mis actividades y horarios')
@section('descripcion', "{$instructor->nombre} {$instructor->apellido} · del {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}")

@section('acciones')
<a class="btn-borde" href="{{ request()->fullUrlWithQuery(['desde' => $desde->copy()->subDays(14)->format('Y-m-d')]) }}">← Anteriores</a>
<a class="btn-borde" href="{{ request()->fullUrlWithQuery(['desde' => $desde->copy()->addDays(14)->format('Y-m-d')]) }}">Siguientes →</a>
@if ($puedeAsignar)
<button type="button" class="btn-primario" data-abrir="modal-turno">+ Asignar turno</button>
<a class="btn-borde" href="{{ route('instructores.index') }}">Volver</a>
@endif
@endsection

@section('contenido')
{{-- HU-149 / HU-150: actividades programadas --}}
<section class="tarjeta">
  <h2>Actividades programadas ({{ $actividades->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Horario</th>
        <th>Actividad</th>
        <th>Espacio</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($actividades as $actividad)
      @php
        $inicio = \Illuminate\Support\Carbon::parse($actividad->fecha_inicio);
        $fin = \Illuminate\Support\Carbon::parse($actividad->fecha_fin);
      @endphp
      <tr>
        <td>{{ ucfirst($inicio->locale('es')->isoFormat('ddd D/MM/YYYY')) }}</td>
        <td>{{ $inicio->format('h:i a') }} – {{ $fin->format('h:i a') }}</td>
        <td>{{ $actividad->nombre_actividad }}</td>
        <td>{{ $actividad->nombre_espacio }}</td>
        <td><span class="badge {{ $actividad->nombre_estado === 'Solicitada' ? 'amarillo' : 'verde' }}">{{ $actividad->nombre_estado }}</span></td>
      </tr>
      @empty
      <tr><td colspan="5">No hay actividades en estas dos semanas.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>

{{-- HU-151: turnos asignados --}}
<section class="tarjeta">
  <h2>Turnos asignados ({{ $turnosAsignados->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Jornada</th>
        <th>Horario</th>
        <th>Estado</th>
        @if ($puedeAsignar)<th>Acciones</th>@endif
      </tr>
    </thead>
    <tbody>
      @forelse ($turnosAsignados as $turno)
      <tr>
        <td>{{ ucfirst(\Illuminate\Support\Carbon::parse($turno->fecha_turno)->locale('es')->isoFormat('ddd D/MM/YYYY')) }}</td>
        <td>{{ $turno->tipo_jornada }}</td>
        <td>{{ substr($turno->hora_inicio, 0, 5) }} – {{ substr($turno->hora_fin, 0, 5) }}</td>
        <td><span class="badge {{ $turno->nombre_estado === 'Vigente' ? 'verde' : 'gris' }}">{{ $turno->nombre_estado }}</span></td>
        @if ($puedeAsignar)
        <td>
          @if ($turno->nombre_estado === 'Vigente')
          <form method="POST" action="{{ route('instructores.turnos.cancelar', [$instructor->personal_id, $turno->turno_asignado_id]) }}" data-confirmar="¿Cancelar este turno?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn-borde">Cancelar</button>
          </form>
          @endif
        </td>
        @endif
      </tr>
      @empty
      <tr><td colspan="{{ $puedeAsignar ? 5 : 4 }}">No hay turnos asignados en estas dos semanas.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>
@endsection

@if ($puedeAsignar)
@push('modales')
<dialog class="modal-plan" id="modal-turno">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2>Asignar turno</h2>
        <p>{{ $instructor->nombre }} {{ $instructor->apellido }}</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ route('instructores.turnos', $instructor->personal_id) }}">
      @csrf
      <label class="campo-plan"><span>Jornada *</span>
        <select name="turno_id" required>
          @foreach ($turnos as $turno)
          <option value="{{ $turno->turno_id }}" @selected((string) old('turno_id') === (string) $turno->turno_id)>{{ $turno->tipo_jornada }} ({{ substr($turno->hora_inicio, 0, 5) }} – {{ substr($turno->hora_fin, 0, 5) }})</option>
          @endforeach
        </select>
      </label>
      <div class="fila-plan">
        <label class="campo-plan"><span>Desde *</span><input type="date" name="fecha_desde" min="{{ date('Y-m-d') }}" value="{{ old('fecha_desde', date('Y-m-d')) }}" required></label>
        <label class="campo-plan"><span>Hasta</span><input type="date" name="fecha_hasta" min="{{ date('Y-m-d') }}" value="{{ old('fecha_hasta') }}"></label>
      </div>
      <div class="campo-plan">
        <span>Días de la semana *</span>
        <div class="acciones-fila">
          @foreach ([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'] as $numero => $dia)
          <label><input type="checkbox" name="dias[]" value="{{ $numero }}" @checked(in_array($numero, old('dias', [1, 2, 3, 4, 5])))> {{ $dia }}</label>
          @endforeach
        </div>
      </div>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Asignar</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
@endif
