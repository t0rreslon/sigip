{{-- HU-178 / HU-52 / HU-176: programar o modificar una actividad (instructor, espacio y horario). --}}
<dialog class="modal-plan" id="modal-programar">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-modal-programar">{{ $programacion ? 'Modificar actividad programada' : 'Programar actividad' }}</h2>
        <p>Se verifica que el espacio y el instructor estén libres en ese horario ({{ $horaApertura }} a {{ $horaCierre }}).</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ old('_accion', $programacion ? route('programaciones.update', $programacion->programacion_actividad_id) : route('programaciones.store')) }}">
      @csrf
      <input type="hidden" name="_method" value="{{ old('_method', $programacion ? 'PUT' : 'POST') }}">
      @php
        $inicio = $programacion ? \Illuminate\Support\Carbon::parse($programacion->fecha_inicio) : null;
        $fin = $programacion ? \Illuminate\Support\Carbon::parse($programacion->fecha_fin) : null;
      @endphp
      <label class="campo-plan"><span>Actividad *</span>
        <select name="actividad_id" required>
          <option value="">Selecciona</option>
          @foreach ($actividades as $actividad)
          @if ($actividad->estado ?? true)
          <option value="{{ $actividad->actividad_id }}" @selected((string) old('actividad_id', $programacion->actividad_id ?? '') === (string) $actividad->actividad_id)>{{ $actividad->nombre_actividad }}</option>
          @endif
          @endforeach
        </select>
      </label>
      <div class="fila-plan">
        <label class="campo-plan"><span>Instructor *</span>
          <select name="personal_id" required>
            <option value="">Selecciona</option>
            @foreach ($instructores as $instructor)
            <option value="{{ $instructor->personal_id }}" @selected((string) old('personal_id', $programacion->personal_id ?? '') === (string) $instructor->personal_id)>{{ $instructor->nombre }} {{ $instructor->apellido }}</option>
            @endforeach
          </select>
        </label>
        <label class="campo-plan"><span>Espacio *</span>
          <select name="espacio_id" required>
            <option value="">Selecciona</option>
            @foreach ($espacios as $espacio)
            <option value="{{ $espacio->espacio_id }}" @selected((string) old('espacio_id', $programacion->espacio_id ?? '') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
            @endforeach
          </select>
        </label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Fecha *</span><input type="date" name="fecha" min="{{ date('Y-m-d') }}" value="{{ old('fecha', $inicio?->format('Y-m-d') ?? date('Y-m-d')) }}" required></label>
        <label class="campo-plan"><span>Desde *</span><input type="time" name="hora_inicio" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_inicio', $inicio?->format('H:i')) }}" required></label>
        <label class="campo-plan"><span>Hasta *</span><input type="time" name="hora_fin" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_fin', $fin?->format('H:i')) }}" required></label>
      </div>
      <input type="hidden" name="registro_obligatorio" value="0">
      <label><input type="checkbox" name="registro_obligatorio" value="1" @checked(old('registro_obligatorio', $programacion->registro_obligatorio ?? false))> Exige inscripción previa</label>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </div>
</dialog>
