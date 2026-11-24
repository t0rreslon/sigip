@extends('layouts.sigip')

@section('titulo', $nombre)
@section('descripcion', $codigo . ' · ' . ($tipo === 'maquina' ? 'Máquina' : 'Implemento') . ' · ' . $elemento->nombre_categoria)

@section('acciones')
<a href="{{ route('inventario.index') }}" class="btn-borde">← Inventario</a>
@if ($tipo === 'maquina')
<button type="button" class="btn-primario" data-abrir="modal-novedad">+ Registrar novedad</button>
@endif
@endsection

@section('contenido')
@php($esAdmin = auth()->user()->tieneTipo('Administrador'))

<section class="tarjeta">
  <h2>Datos del elemento</h2>
  <dl class="detalle-grid">
    <div><dt>Código</dt><dd>{{ $codigo }}</dd></div>
    <div><dt>Categoría</dt><dd>{{ $elemento->nombre_categoria }}</dd></div>
    <div><dt>Estado</dt><dd>{{ $elemento->nombre_estado }}</dd></div>
    @if ($tipo === 'maquina')
    <div><dt>Ubicación</dt><dd>{{ $elemento->nombre_espacio ?? 'Sin ubicación' }}</dd></div>
    <div><dt>Descripción</dt><dd>{{ $elemento->descripcion ?: 'Sin descripción' }}</dd></div>
    @endif
  </dl>

  {{-- HU-158: modificar datos y estado (solo Administrador) --}}
  @if ($esAdmin)
  <hr>
  <form method="POST" action="{{ route('inventario.update', [$tipo, $tipo === 'maquina' ? $elemento->maquina_id : $elemento->implemento_id]) }}">
    @csrf
    @method('PUT')
    @if ($tipo === 'maquina')
    <div class="fila-plan">
      <label class="campo-plan"><span>Ubicación *</span>
        <select name="espacio_id" required>
          @foreach ($espacios as $espacio)
          <option value="{{ $espacio->espacio_id }}" @selected((int) old('espacio_id', $elemento->espacio_id) === (int) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
          @endforeach
        </select>
      </label>
      <label class="campo-plan"><span>Estado *</span>
        <select name="estado_maquina_id" required>
          @foreach ($estados as $estado)
          <option value="{{ $estado->estado_maquina_id }}" @selected((int) old('estado_maquina_id', $elemento->estado_maquina_id) === (int) $estado->estado_maquina_id)>{{ $estado->nombre_estado }}</option>
          @endforeach
        </select>
      </label>
    </div>
    <label class="campo-plan"><span>Descripción / observaciones</span>
      <input type="text" name="descripcion" maxlength="1000" value="{{ old('descripcion', $elemento->descripcion) }}">
    </label>
    @else
    <div class="fila-plan">
      <label class="campo-plan"><span>Categoría *</span>
        <select name="categoria_implemento_id" required>
          @foreach ($categorias as $categoria)
          <option value="{{ $categoria->categoria_implemento_id }}" @selected((int) old('categoria_implemento_id', $elemento->categoria_implemento_id) === (int) $categoria->categoria_implemento_id)>{{ $categoria->nombre_categoria }}</option>
          @endforeach
        </select>
      </label>
      <label class="campo-plan"><span>Estado *</span>
        <select name="estado_implemento_id" required @disabled($elemento->nombre_estado === 'Ocupado')>
          @foreach ($estados as $estado)
          <option value="{{ $estado->estado_implemento_id }}" @selected((int) old('estado_implemento_id', $elemento->estado_implemento_id) === (int) $estado->estado_implemento_id)>{{ $estado->nombre_estado }}</option>
          @endforeach
        </select>
      </label>
    </div>
    @if ($elemento->nombre_estado === 'Ocupado')
    <p class="nota-plan">El implemento está prestado: su estado cambia al registrar la devolución.</p>
    @endif
    @endif
    <div class="modal-plan-acciones">
      <button type="submit" class="btn-primario" @disabled($tipo === 'implemento' && $elemento->nombre_estado === 'Ocupado')>Guardar cambios</button>
    </div>
  </form>
  @endif
</section>

@if ($tipo === 'maquina')
{{-- HU-162: novedades y mantenimientos del equipo --}}
<section class="tarjeta">
  <h2>Novedades, inspecciones y mantenimientos ({{ $novedades->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Detalle</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($novedades as $novedad)
      <tr>
        <td>{{ $novedad->fecha_mantenimiento ? \Illuminate\Support\Carbon::parse($novedad->fecha_mantenimiento)->format('d/m/Y') : '—' }}</td>
        <td>{{ $novedad->detalles_mantenimiento }}</td>
        <td><span class="badge {{ $novedad->nombre_estado === 'Finalizado' ? 'verde' : 'amarillo' }}">{{ $novedad->nombre_estado }}</span></td>
        <td>
          @if ($novedad->nombre_estado !== 'Finalizado')
          <form method="POST" action="{{ route('inventario.novedades.update', [$elemento->maquina_id, $novedad->mantenimiento_maquina_id]) }}" class="acciones-fila">
            @csrf
            @method('PATCH')
            <select name="estado_maquina_mantenimiento_id">
              @foreach ($estadosMantenimiento as $estado)
              <option value="{{ $estado->estado_maquina_mantenimiento_id }}" @selected($estado->nombre_estado === $novedad->nombre_estado)>{{ $estado->nombre_estado }}</option>
              @endforeach
            </select>
            <button type="submit" class="btn-borde">Actualizar</button>
          </form>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="4">No hay novedades registradas para este equipo.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>
@else
{{-- HU-165: historial de préstamos del implemento --}}
<section class="tarjeta">
  <h2>Historial de préstamos ({{ $prestamos->count() }})</h2>
  <table class="tabla">
    <thead>
      <tr>
        <th>Préstamo</th>
        <th>Prestado a</th>
        <th>Fecha de préstamo</th>
        <th>Devolución</th>
        <th>Estado al devolver</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($prestamos as $prestamo)
      <tr>
        <td><a href="{{ route('prestamos.show', $prestamo->prestamo_id) }}">#{{ $prestamo->prestamo_id }}</a></td>
        <td>{{ $prestamo->destinatario }}<br><small>{{ $prestamo->tipo_destinatario }}</small></td>
        <td>{{ \Illuminate\Support\Carbon::parse($prestamo->fecha_prestado)->format('d/m/Y h:i a') }}</td>
        <td>{{ $prestamo->fecha_devuelto ? \Illuminate\Support\Carbon::parse($prestamo->fecha_devuelto)->format('d/m/Y h:i a') : 'Sin devolver' }}</td>
        <td>{{ $prestamo->estado_devolucion }}</td>
      </tr>
      @empty
      <tr><td colspan="5">Este implemento no se ha prestado.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>
@endif

{{-- HU-160: movimientos y cambios del elemento --}}
<section class="tarjeta">
  <h2>Cambios registrados</h2>
  <x-historial-cambios :registros="$historial" />
</section>
@endsection

@if ($tipo === 'maquina')
@push('modales')
{{-- HU-161: registrar novedad --}}
<dialog class="modal-plan" id="modal-novedad">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2>Registrar novedad</h2>
        <p>{{ $codigo }} · {{ $nombre }}</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ route('inventario.novedades.store', $elemento->maquina_id) }}">
      @csrf
      <div class="fila-plan">
        <label class="campo-plan"><span>Tipo *</span>
          <select name="tipo_novedad" required>
            @foreach (['Novedad', 'Inspección', 'Mantenimiento'] as $tipoNovedad)
            <option value="{{ $tipoNovedad }}" @selected(old('tipo_novedad') === $tipoNovedad)>{{ $tipoNovedad }}</option>
            @endforeach
          </select>
        </label>
        <label class="campo-plan"><span>Fecha *</span><input type="date" name="fecha_mantenimiento" max="{{ date('Y-m-d') }}" value="{{ old('fecha_mantenimiento', date('Y-m-d')) }}" required></label>
      </div>
      <label class="campo-plan"><span>Detalle *</span><input type="text" name="detalles_mantenimiento" maxlength="2000" value="{{ old('detalles_mantenimiento') }}" placeholder="Ej. Banda de arrastre irregular" required></label>
      <label class="campo-plan"><span>Estado *</span>
        <select name="estado_maquina_mantenimiento_id" required>
          @foreach ($estadosMantenimiento as $estado)
          <option value="{{ $estado->estado_maquina_mantenimiento_id }}">{{ $estado->nombre_estado }}</option>
          @endforeach
        </select>
      </label>
      <label><input type="checkbox" name="bloquear" value="1" @checked(old('bloquear'))> Bloquear el uso: la máquina pasa a "Mantenimiento"</label>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Registrar</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
@endif
