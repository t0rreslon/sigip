@extends('layouts.sigip')

@section('titulo', 'Operadores')
@section('descripcion', 'Cuentas del personal de recepción autorizado para operar SIGIP.')

@section('acciones')
<a href="{{ route('operadores.movimientos') }}" class="btn-borde">Historial de acciones</a>
<button type="button" class="btn-primario" data-abrir="modal-operador">+ Crear operador</button>
@endsection

@section('contenido')
<section class="tarjeta">
  {{-- HU-191: buscar operadores --}}
  <form class="filtros" method="GET" action="{{ route('operadores.index') }}">
    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, documento o usuario">
    <select name="estado" data-autoenviar>
      <option value="">Cuenta: todas</option>
      <option value="1" @selected(request('estado') === '1')>Activas</option>
      <option value="0" @selected(request('estado') === '0')>Inactivas</option>
    </select>
    <button type="submit" class="btn-borde">Buscar</button>
    @if (request()->hasAny(['buscar', 'estado']))
    <a href="{{ route('operadores.index') }}" class="btn-borde">Limpiar</a>
    @endif
  </form>

  {{-- HU-187: lista de operadores --}}
  <table class="tabla">
    <thead>
      <tr>
        <th>Operador</th>
        <th>Usuario</th>
        <th>Contacto</th>
        <th>Creada</th>
        <th>Última acción</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($operadores as $operador)
      <tr>
        <td>{{ $operador->nombre }} {{ $operador->apellido }}<br><small>{{ $operador->documento }}</small></td>
        <td>{{ $operador->login }}</td>
        <td>{{ $operador->correo ?? 'Sin correo' }}<br><small>{{ $operador->telefono ?? '' }}</small></td>
        <td>{{ \Illuminate\Support\Carbon::parse($operador->fecha_creacion)->format('d/m/Y') }}</td>
        <td>{{ $operador->ultima_accion ? \Illuminate\Support\Carbon::parse($operador->ultima_accion)->format('d/m/Y h:i a') : 'Sin acciones' }}</td>
        <td><span class="badge {{ $operador->estado_cuenta ? 'verde' : 'gris' }}">{{ $operador->estado_cuenta ? 'Activa' : 'Inactiva' }}</span></td>
        <td>
          <div class="acciones-fila">
            {{-- HU-190 --}}
            <form method="POST" action="{{ route('operadores.estado', $operador->cuenta_id) }}" data-confirmar="¿{{ $operador->estado_cuenta ? 'Desactivar' : 'Activar' }} la cuenta de {{ $operador->login }}?">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn-borde">{{ $operador->estado_cuenta ? 'Desactivar' : 'Activar' }}</button>
            </form>
            <a href="{{ route('operadores.movimientos', ['cuenta' => $operador->cuenta_id]) }}" class="btn-borde">Acciones</a>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="7">No hay operadores que coincidan con la búsqueda.</td></tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$operadores" etiqueta="operadores" />
</section>
@endsection

@push('modales')
{{-- HU-186: crear cuenta de operador --}}
<dialog class="modal-plan" id="modal-operador">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2>Crear operador</h2>
        <p>Datos personales y credenciales de acceso</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ route('operadores.store') }}">
      @csrf
      <input type="hidden" name="_modal" value="modal-operador">
      <div class="fila-plan">
        <label class="campo-plan"><span>Nombre *</span><input type="text" name="nombre" maxlength="50" value="{{ old('nombre') }}" required></label>
        <label class="campo-plan"><span>Apellido *</span><input type="text" name="apellido" maxlength="50" value="{{ old('apellido') }}" required></label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Tipo de documento *</span>
          <select name="tipo_documento_id" required>
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
            @foreach ($generos as $genero)
            <option value="{{ $genero->genero_id }}" @selected((string) old('genero_id') === (string) $genero->genero_id)>{{ $genero->nombre_genero }}</option>
            @endforeach
          </select>
        </label>
        <label class="campo-plan"><span>Correo</span><input type="email" name="correo" maxlength="80" value="{{ old('correo') }}"></label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Teléfono</span><input type="tel" name="telefono" pattern="[0-9]{7,11}" maxlength="11" value="{{ old('telefono') }}"></label>
        <label class="campo-plan"><span>Usuario *</span><input type="text" name="login" maxlength="50" value="{{ old('login') }}" autocomplete="off" required></label>
      </div>
      <div class="fila-plan">
        <label class="campo-plan"><span>Contraseña *</span><input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
        <label class="campo-plan"><span>Confirmar *</span><input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
      </div>
      <p class="nota-plan">Mínimo 10 caracteres con mayúsculas, minúsculas, números y un símbolo. El correo se usa para recuperar la contraseña.</p>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Crear operador</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
