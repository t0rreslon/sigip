@extends('layouts.sigip')

@section('titulo', 'Instructores')
@section('descripcion', 'Registra, consulta y administra a los instructores del gimnasio, sus cuentas y horarios.')

@section('acciones')
<a href="{{ route('instructores.exportar', request()->query()) }}" class="btn-exportar">Exportar PDF</a>
<button type="button" class="btn-primario" data-abrir="modal-instructor" data-accion="{{ route('instructores.store') }}"
  data-campos='{"_method":"POST","tipo_documento_id":"","documento":"","nombre":"","apellido":"","genero_id":"","fecha_inicio":"{{ date('Y-m-d') }}","telefono":"","correo":""}'
  data-textos='{"titulo-modal-instructor":"Registrar instructor"}'>+ Registrar instructor</button>
@endsection

@section('contenido')
<section class="tarjeta">
  {{-- HU-33: buscar instructores --}}
  <form class="filtros" method="GET" action="{{ route('instructores.index') }}">
    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, documento o correo">
    <select name="estado" data-autoenviar>
      <option value="">Estado: todos</option>
      @foreach ($estados as $estado)
      <option value="{{ $estado->estado_personal_id }}" @selected((string) request('estado') === (string) $estado->estado_personal_id)>{{ $estado->nombre_estado }}</option>
      @endforeach
    </select>
    <select name="cuenta" data-autoenviar>
      <option value="">Cuenta: todas</option>
      <option value="con" @selected(request('cuenta') === 'con')>Con cuenta</option>
      <option value="sin" @selected(request('cuenta') === 'sin')>Sin cuenta</option>
    </select>
    <button type="submit" class="btn-borde">Buscar</button>
    @if (request()->hasAny(['buscar', 'estado', 'cuenta']))
    <a href="{{ route('instructores.index') }}" class="btn-borde">Limpiar</a>
    @endif
  </form>

  {{-- HU-32: instructores registrados --}}
  <table class="tabla">
    <thead>
      <tr>
        <th>Instructor</th>
        <th>Contacto</th>
        <th>Desde</th>
        <th>Estado</th>
        <th>Cuenta</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($instructores as $instructor)
      <tr>
        <td>{{ $instructor->nombre }} {{ $instructor->apellido }}<br><small>{{ $instructor->documento }}</small></td>
        <td>{{ $instructor->correo ?? 'Sin correo' }}<br><small>{{ $instructor->telefono ?? 'Sin teléfono' }}</small></td>
        <td>{{ \Illuminate\Support\Carbon::parse($instructor->fecha_inicio)->format('d/m/Y') }}</td>
        <td><span class="badge {{ $instructor->nombre_estado === 'Activo' ? 'verde' : ($instructor->nombre_estado === 'Retirado' ? 'rojo' : 'amarillo') }}">{{ $instructor->nombre_estado }}</span></td>
        <td>
          @if ($instructor->cuenta_id)
          {{ $instructor->login }}
          <br><small>{{ $instructor->estado_cuenta ? 'Activa' : 'Inactiva' }}</small>
          @else
          <button type="button" class="btn-borde" data-abrir="modal-cuenta" data-accion="{{ route('instructores.cuenta', $instructor->personal_id) }}"
            data-textos='{{ json_encode(["nombre-cuenta" => "{$instructor->nombre} {$instructor->apellido}"]) }}'>Crear cuenta</button>
          @endif
        </td>
        <td>
          <div class="acciones-fila">
            <button type="button" class="btn-borde" data-abrir="modal-instructor" data-accion="{{ route('instructores.update', $instructor->personal_id) }}"
              data-campos='{{ json_encode([
                "_method" => "PUT",
                "tipo_documento_id" => $instructor->tipo_documento_id,
                "documento" => $instructor->documento,
                "nombre" => $instructor->nombre,
                "apellido" => $instructor->apellido,
                "genero_id" => $instructor->genero_id,
                "fecha_inicio" => \Illuminate\Support\Carbon::parse($instructor->fecha_inicio)->format("Y-m-d"),
                "telefono" => $instructor->telefono,
                "correo" => $instructor->correo,
              ]) }}'
              data-textos='{"titulo-modal-instructor":"Editar instructor"}'>Editar</button>
            <button type="button" class="btn-borde" data-abrir="modal-estado" data-accion="{{ route('instructores.estado', $instructor->personal_id) }}"
              data-campos='{{ json_encode(["estado_personal_id" => $instructor->estado_personal_id]) }}'
              data-textos='{{ json_encode(["nombre-estado" => "{$instructor->nombre} {$instructor->apellido}"]) }}'>Estado</button>
            <a class="btn-borde" href="{{ route('instructores.horario', $instructor->personal_id) }}">Horario</a>
            <a class="btn-borde" href="{{ route('instructores.historial', $instructor->personal_id) }}">Historial</a>
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="6">No hay instructores que coincidan con la búsqueda.</td>
      </tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$instructores" etiqueta="instructores" />
</section>
@endsection

@push('modales')
{{-- HU-31 / HU-145: registrar o editar --}}
<dialog class="modal-plan" id="modal-instructor">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-modal-instructor">{{ old('_method') === 'PUT' ? 'Editar instructor' : 'Registrar instructor' }}</h2>
        <p>Datos personales del instructor</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ old('_accion', route('instructores.store')) }}">
      @csrf
      <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
      <input type="hidden" name="_modal" value="modal-instructor">
      <div class="fila-plan">
        <label class="campo-plan"><span>Nombre *</span><input type="text" name="nombre" maxlength="50" value="{{ old('nombre') }}" required></label>
        <label class="campo-plan"><span>Apellido *</span><input type="text" name="apellido" maxlength="50" value="{{ old('apellido') }}" required></label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Tipo de documento *</span>
          <select name="tipo_documento_id" required>
            <option value="">Selecciona</option>
            @foreach ($tiposDocumento as $tipo)
            <option value="{{ $tipo->tipo_documento_id }}" @selected((string) old('tipo_documento_id') === (string) $tipo->tipo_documento_id)>{{ $tipo->nombre_tipo_documento }}</option>
            @endforeach
          </select>
        </label>
        <label class="campo-plan"><span>Documento *</span><input type="text" name="documento" inputmode="numeric" pattern="[0-9]{6,12}" maxlength="12" value="{{ old('documento') }}" required></label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Género *</span>
          <select name="genero_id" required>
            <option value="">Selecciona</option>
            @foreach ($generos as $genero)
            <option value="{{ $genero->genero_id }}" @selected((string) old('genero_id') === (string) $genero->genero_id)>{{ $genero->nombre_genero }}</option>
            @endforeach
          </select>
        </label>
        <label class="campo-plan"><span>Fecha de vinculación</span><input type="date" name="fecha_inicio" max="{{ date('Y-m-d') }}" value="{{ old('fecha_inicio', date('Y-m-d')) }}"></label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Teléfono</span><input type="tel" name="telefono" pattern="[0-9]{7,11}" maxlength="11" value="{{ old('telefono') }}"></label>
        <label class="campo-plan"><span>Correo</span><input type="email" name="correo" maxlength="80" value="{{ old('correo') }}"></label>
      </div>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </div>
</dialog>

{{-- HU-146: cambiar estado --}}
<dialog class="modal-plan" id="modal-estado">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2>Cambiar estado</h2>
        <p id="nombre-estado"></p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST">
      @csrf
      @method('PATCH')
      <label class="campo-plan"><span>Estado *</span>
        <select name="estado_personal_id" required>
          @foreach ($estados as $estado)
          <option value="{{ $estado->estado_personal_id }}">{{ $estado->nombre_estado }}</option>
          @endforeach
        </select>
      </label>
      <p class="nota-plan">Si el instructor no queda "Activo", su cuenta se desactiva y no podrá ingresar al sistema.</p>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </div>
</dialog>

{{-- HU-147: crear cuenta --}}
<dialog class="modal-plan" id="modal-cuenta">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2>Crear cuenta de instructor</h2>
        <p id="nombre-cuenta"></p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ old('_accion') }}">
      @csrf
      <input type="hidden" name="_modal" value="modal-cuenta">
      <label class="campo-plan"><span>Usuario *</span><input type="text" name="login" maxlength="50" value="{{ old('login') }}" autocomplete="off" required></label>
      <div class="fila-plan">
        <label class="campo-plan"><span>Contraseña *</span><input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
        <label class="campo-plan"><span>Confirmar *</span><input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
      </div>
      <p class="nota-plan">Mínimo 10 caracteres con mayúsculas, minúsculas, números y un símbolo. Comparte la contraseña con el instructor de forma segura; podrá cambiarla en "Mi perfil".</p>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Crear cuenta</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
