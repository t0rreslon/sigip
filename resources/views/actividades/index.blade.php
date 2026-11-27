@extends('layouts.sigip')

@section('titulo', 'Actividades')
@section('descripcion', 'Catálogo de actividades, programación con instructores y espacios, y solicitudes de los instructores.')

@section('acciones')
<a href="{{ route('actividades.reporte') }}" class="btn-borde">Participación y asistencia</a>
@if (auth()->user()->tieneTipo('Administrador'))
<button type="button" class="btn-borde" data-abrir-catalogo>Gestionar catálogo</button>
<button type="button" class="btn-borde" data-abrir="modal-actividad" data-accion="{{ route('actividades.store') }}"
  data-campos='{"_method":"POST","nombre_actividad":"","descripcion":"","maximo_participantes":"","registro_obligatorio":false,"estado":"1"}'
  data-textos='{"titulo-modal-actividad":"Registrar actividad"}'>+ Registrar actividad</button>
<button type="button" class="btn-primario" data-abrir="modal-programar" data-accion="{{ route('programaciones.store') }}"
  data-campos='{"_method":"POST"}' data-textos='{"titulo-modal-programar":"Programar actividad"}'>+ Programar</button>
@endif
@endsection

@section('contenido')
@vite(['resources/js/catalogo-actividades.js'])
{{-- HU-53: catálogo de actividades en una franja compacta de chips (la programación es lo principal). --}}
@php
  $esAdminCatalogo = auth()->user()->tieneTipo('Administrador');
  $camposActividad = fn ($a) => json_encode([
      '_method' => 'PUT',
      'nombre_actividad' => $a->nombre_actividad,
      'descripcion' => $a->descripcion,
      'maximo_participantes' => $a->maximo_participantes,
      'registro_obligatorio' => (bool) $a->registro_obligatorio,
      'estado' => $a->estado ? '1' : '0',
  ]);
  $detalleActividad = fn ($a) => collect([
      $a->maximo_participantes ? 'cupo ' . $a->maximo_participantes : 'sin límite',
      $a->registro_obligatorio ? 'inscripción previa' : null,
  ])->filter()->implode(' · ');
@endphp
<section class="tarjeta catalogo-actividades" aria-labelledby="titulo-catalogo">
  <div class="catalogo-cabecera">
    <h2 id="titulo-catalogo">Catálogo de actividades</h2>
    <span class="catalogo-resumen">{{ $actividades->where('estado', true)->count() }} activas · {{ $actividades->where('estado', false)->count() }} inactivas</span>
  </div>
  <ul class="catalogo-chips">
    @foreach ($actividades->sortByDesc('estado') as $actividad)
    <li>
      @if ($esAdminCatalogo)
      <button type="button" class="chip-actividad {{ $actividad->estado ? '' : 'inactiva' }}"
        data-abrir="modal-actividad" data-accion="{{ route('actividades.update', $actividad->actividad_id) }}"
        data-campos="{{ $camposActividad($actividad) }}"
        data-textos='{"titulo-modal-actividad":"Editar actividad"}'
        title="{{ $actividad->descripcion ? $actividad->descripcion . ' · ' : '' }}Clic para editar">
        <strong>{{ $actividad->nombre_actividad }}</strong>
        <span>{{ $detalleActividad($actividad) }}</span>
      </button>
      @else
      <span class="chip-actividad {{ $actividad->estado ? '' : 'inactiva' }}" title="{{ $actividad->descripcion }}">
        <strong>{{ $actividad->nombre_actividad }}</strong>
        <span>{{ $detalleActividad($actividad) }}</span>
      </span>
      @endif
    </li>
    @endforeach
  </ul>
</section>

<section class="tarjeta">
  <div class="pestanas">
    <a href="{{ route('actividades.index', ['vista' => 'proximas']) }}" class="{{ $vista === 'proximas' ? 'activa' : '' }}">Programadas (próximas)</a>
    <a href="{{ route('actividades.index', ['vista' => 'solicitadas']) }}" class="{{ $vista === 'solicitadas' ? 'activa' : '' }}">Solicitudes de instructores ({{ $solicitadas }})</a>
    <a href="{{ route('actividades.index', ['vista' => 'historial']) }}" class="{{ $vista === 'historial' ? 'activa' : '' }}">Historial</a>
  </div>

  <form class="filtros" method="GET" action="{{ route('actividades.index') }}">
    <input type="hidden" name="vista" value="{{ $vista }}">
    <select name="actividad" data-autoenviar>
      <option value="">Actividad: todas</option>
      @foreach ($actividades as $actividad)
      <option value="{{ $actividad->actividad_id }}" @selected((string) request('actividad') === (string) $actividad->actividad_id)>{{ $actividad->nombre_actividad }}</option>
      @endforeach
    </select>
    <select name="instructor" data-autoenviar>
      <option value="">Instructor: todos</option>
      @foreach ($instructores as $instructor)
      <option value="{{ $instructor->personal_id }}" @selected((string) request('instructor') === (string) $instructor->personal_id)>{{ $instructor->nombre }} {{ $instructor->apellido }}</option>
      @endforeach
    </select>
    <select name="espacio" data-autoenviar>
      <option value="">Espacio: todos</option>
      @foreach ($espacios as $espacio)
      <option value="{{ $espacio->espacio_id }}" @selected((string) request('espacio') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
      @endforeach
    </select>
  </form>

  <table class="tabla">
    <thead>
      <tr><th>Actividad</th><th>Fecha y horario</th><th>Instructor</th><th>Espacio</th><th>Inscritos / cupo</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      @forelse ($programaciones as $programacion)
      @php
        $inicio = \Illuminate\Support\Carbon::parse($programacion->fecha_inicio);
        $fin = \Illuminate\Support\Carbon::parse($programacion->fecha_fin);
      @endphp
      <tr>
        <td>{{ $programacion->nombre_actividad }}</td>
        <td>{{ ucfirst($inicio->locale('es')->isoFormat('ddd D/MM/YYYY')) }}<br><small>{{ $inicio->format('h:i a') }} – {{ $fin->format('h:i a') }}</small></td>
        <td>{{ $programacion->instructor }}</td>
        <td>{{ $programacion->nombre_espacio }}</td>
        <td>{{ $programacion->inscritos }} / {{ $programacion->maximo_participantes ?? 'sin límite' }}</td>
        <td><span class="badge {{ match ($programacion->nombre_estado) { 'Pendiente', 'En curso' => 'verde', 'Finalizada' => 'gris', 'Solicitada' => 'amarillo', default => 'rojo' } }}">{{ $programacion->nombre_estado === 'Pendiente' ? 'Programada' : $programacion->nombre_estado }}</span></td>
        <td>
          <div class="acciones-fila">
            <a href="{{ route('programaciones.show', $programacion->programacion_actividad_id) }}" class="btn-borde">Ver</a>
            @if ($programacion->nombre_estado === 'Solicitada' && auth()->user()->tieneTipo('Administrador'))
            {{-- HU-201: validar actividades solicitadas --}}
            <form method="POST" action="{{ route('programaciones.validar', $programacion->programacion_actividad_id) }}">
              @csrf
              @method('PATCH')
              <input type="hidden" name="accion" value="aprobar">
              <button type="submit" class="btn-primario">Aprobar</button>
            </form>
            <form method="POST" action="{{ route('programaciones.validar', $programacion->programacion_actividad_id) }}" data-confirmar="¿Rechazar esta solicitud?">
              @csrf
              @method('PATCH')
              <input type="hidden" name="accion" value="rechazar">
              <button type="submit" class="btn-borde">Rechazar</button>
            </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="7">No hay actividades para mostrar.</td></tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$programaciones" etiqueta="actividades" />
</section>

@endsection

@push('modales')
@if (auth()->user()->tieneTipo('Administrador'))
{{-- Pop-up "Gestionar catálogo": todas las actividades con búsqueda, estado y edición. --}}
<dialog class="modal-plan modal-registros" id="modal-catalogo" aria-labelledby="titulo-modal-catalogo">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-modal-catalogo">Gestionar catálogo</h2>
        <p>{{ $actividades->count() }} actividades · {{ $actividades->where('estado', true)->count() }} activas</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar-catalogo aria-label="Cerrar">✕</button>
    </div>

    <div class="filtros filtros-catalogo">
      <input type="search" id="buscar-catalogo" placeholder="Buscar actividad" aria-label="Buscar actividad" autocomplete="off">
      <select id="estado-catalogo" aria-label="Estado">
        <option value="">Estado: todas</option>
        <option value="1">Activas</option>
        <option value="0">Inactivas</option>
      </select>
      <button type="button" class="btn-primario" data-abrir="modal-actividad" data-accion="{{ route('actividades.store') }}"
        data-campos='{"_method":"POST","nombre_actividad":"","descripcion":"","maximo_participantes":"","registro_obligatorio":false,"estado":"1"}'
        data-textos='{"titulo-modal-actividad":"Registrar actividad"}'>+ Registrar actividad</button>
    </div>

    <div class="tabla-desplazable">
      <table class="tabla">
        <thead>
          <tr><th>Actividad</th><th>Cupo máximo</th><th>Inscripción previa</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          @foreach ($actividades as $actividad)
          <tr data-catalogo-nombre="{{ mb_strtolower($actividad->nombre_actividad . ' ' . $actividad->descripcion) }}" data-catalogo-estado="{{ $actividad->estado ? '1' : '0' }}">
            <td>{{ $actividad->nombre_actividad }}@if ($actividad->descripcion)<br><small>{{ $actividad->descripcion }}</small>@endif</td>
            <td>{{ $actividad->maximo_participantes ?? 'Sin límite' }}</td>
            <td>{{ $actividad->registro_obligatorio ? 'Obligatoria' : 'No' }}</td>
            <td><span class="badge {{ $actividad->estado ? 'verde' : 'gris' }}">{{ $actividad->estado ? 'Activa' : 'Inactiva' }}</span></td>
            <td>
              <button type="button" class="btn-borde" data-abrir="modal-actividad" data-accion="{{ route('actividades.update', $actividad->actividad_id) }}"
                data-campos="{{ $camposActividad($actividad) }}"
                data-textos='{"titulo-modal-actividad":"Editar actividad"}'>Editar</button>
            </td>
          </tr>
          @endforeach
          <tr id="catalogo-sin-resultados" hidden><td colspan="5">No hay actividades que coincidan con la búsqueda.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</dialog>
@endif
@include('actividades._modal-programar', ['programacion' => null])

{{-- HU-51: registrar / editar actividad del catálogo --}}
<dialog class="modal-plan" id="modal-actividad">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-modal-actividad">Registrar actividad</h2>
        <p>Catálogo de actividades del gimnasio</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <form method="POST" action="{{ \App\Support\Busqueda::accionSegura(old('_accion'), route('actividades.store')) }}">
      @csrf
      <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
      <label class="campo-plan"><span>Nombre *</span><input type="text" name="nombre_actividad" maxlength="50" value="{{ old('nombre_actividad') }}" required></label>
      <label class="campo-plan"><span>Descripción</span><input type="text" name="descripcion" maxlength="1000" value="{{ old('descripcion') }}"></label>
      <div class="fila-plan">
        <label class="campo-plan"><span>Cupo máximo</span><input type="number" name="maximo_participantes" min="1" max="1000" value="{{ old('maximo_participantes') }}" placeholder="Sin límite"></label>
        <label class="campo-plan"><span>Estado</span>
          <select name="estado">
            <option value="1">Activa</option>
            <option value="0">Inactiva</option>
          </select>
        </label>
      </div>
      <input type="hidden" name="registro_obligatorio" value="0">
      <label><input type="checkbox" name="registro_obligatorio" value="1" @checked(old('registro_obligatorio'))> Exige inscripción previa</label>
      <div class="modal-plan-acciones">
        <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </div>
</dialog>
@endpush
