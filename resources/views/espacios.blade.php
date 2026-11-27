<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGIP | Espacios</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css', 'resources/js/espacios.js', 'resources/js/sigip.js'])
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>

<body>
  <div class="flexbox">
    <x-panel-izq />
    <div class="contenido">
      <x-nav_bar />
      <main class="pagina">
        <div class="pagina-titulo">
          <div>
            <h1>Espacios</h1>
            <p>Salas, zonas y escenarios del gimnasio que se pueden reservar.</p>
          </div>
          @if (auth()->user()->tieneTipo('Administrador'))
          <button class="btn-primario" id="abrir-registrar-espacio">+ Registrar espacio</button>
          @endif
        </div>

        @if (session('success'))
        <p class="mensaje-exito">{{ session('success') }}</p>
        @endif

        @if ($errors->imagen->any())
        <p class="mensaje-error">{{ $errors->imagen->first('imagen') }}</p>
        @endif

        @if ($errors->any())
        <div class="mensaje-error">
          <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <div class="resumen">
          @foreach ($estados as $estado)
          <span class="chip {{ match ($estado->nombre_estado) { 'Disponible' => 'verde', 'Reservado' => 'amarillo', 'Mantenimiento' => 'morado', default => 'rojo' } }}">
            {{ (int) ($conteos[$estado->estado_espacio_id] ?? 0) }} {{ mb_strtolower($estado->nombre_estado) }}
          </span>
          @endforeach
        </div>

        <section class="tarjeta">
          {{-- HU-46: filtros --}}
          <form class="filtros" method="GET" action="{{ route('espacios.index') }}" id="form-filtros-espacios">
            <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o descripción...">
            <select name="estado" data-autoenviar>
              <option value="">Estado: todos</option>
              @foreach ($estados as $estado)
              <option value="{{ $estado->estado_espacio_id }}" @selected((string) request('estado') === (string) $estado->estado_espacio_id)>
                {{ $estado->nombre_estado }}
              </option>
              @endforeach
            </select>
            <input type="number" name="capacidad_min" min="1" value="{{ request('capacidad_min') }}" placeholder="Capacidad mínima">
            <button type="submit" class="btn-borde">Filtrar</button>
            @if (request()->hasAny(['buscar', 'estado', 'capacidad_min']))
            <a href="{{ route('espacios.index') }}" class="btn-borde">Limpiar</a>
            @endif
          </form>

          <div class="espacios-grid">
            @forelse ($espacios as $espacio)
            @php
              $deshabilitado = in_array($espacio->nombre_estado, \App\Models\Espacio::ESTADOS_DESHABILITADOS, true);
              $fotoEspacio = \App\Support\Imagenes::url($espacio->imagen, 'espacios');
            @endphp
            <article class="ficha-espacio {{ $deshabilitado ? 'deshabilitado' : '' }}">
              <div class="ficha-espacio-foto">
                @if ($fotoEspacio)
                <img src="{{ $fotoEspacio }}" alt="Foto de {{ $espacio->nombre_espacio }}" loading="lazy">
                @else
                <div class="ficha-espacio-sin-foto" aria-hidden="true">
                  <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/></svg>
                </div>
                @endif
                <span class="badge ficha-espacio-estado {{ match ($espacio->nombre_estado) { 'Disponible' => 'verde', 'Reservado' => 'amarillo', default => 'rojo' } }}">{{ $espacio->nombre_estado }}</span>
                @if (auth()->user()->tieneTipo('Administrador'))
                <form method="POST" action="{{ route('espacios.imagen', $espacio->espacio_id) }}" enctype="multipart/form-data" class="form-foto-espacio">
                  @csrf
                  <label class="btn-cambiar-foto" title="Cambiar foto">
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" data-autoenviar>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    <span class="visualmente-oculto">Cambiar foto de {{ $espacio->nombre_espacio }}</span>
                  </label>
                </form>
                @endif
              </div>

              <div class="ficha-espacio-cuerpo">
                <h3>{{ $espacio->nombre_espacio }}</h3>
                <dl class="ficha-espacio-datos">
                  <div><dt>Capacidad</dt><dd>{{ $espacio->capacidad ? $espacio->capacidad . ' personas' : 'Sin definir' }}</dd></div>
                  <div><dt>Reservas activas</dt><dd>
                    @if ($espacio->reservas_activas > 0)
                    {{ $espacio->reservas_activas }} · próxima {{ \Illuminate\Support\Carbon::parse($espacio->proxima)->format('d/m h:i a') }}
                    @else
                    Sin reservas
                    @endif
                  </dd></div>
                </dl>
                @if ($espacio->descripcion)
                <p class="ficha-espacio-descripcion" title="{{ $espacio->descripcion }}">{{ $espacio->descripcion }}</p>
                @endif
              </div>

              <div class="ficha-espacio-acciones">
                @if (auth()->user()->tieneTipo('Administrador'))
                @if ($deshabilitado)
                <form method="POST" action="{{ route('espacios.estado', $espacio->espacio_id) }}">
                  @csrf
                  @method('PATCH')
                  @foreach (['buscar', 'estado', 'capacidad_min', 'page'] as $filtro)
                  @if (request()->filled($filtro))
                  <input type="hidden" name="{{ $filtro }}" value="{{ request($filtro) }}">
                  @endif
                  @endforeach
                  <input type="hidden" name="accion" value="habilitar">
                  <button type="submit" class="btn-borde">Habilitar</button>
                </form>
                @else
                <button
                  type="button"
                  class="btn-borde btn-deshabilitar-espacio"
                  data-accion="{{ route('espacios.estado', $espacio->espacio_id) }}"
                  data-nombre="{{ $espacio->nombre_espacio }}">
                  Deshabilitar
                </button>
                @endif
                @endif
                <a class="btn-borde" href="{{ route('espacios.show', $espacio->espacio_id) }}">Ver detalle</a>
                <a class="btn-primario" href="{{ route('reservas.index', ['espacio' => $espacio->espacio_id]) }}">Ver reservas</a>
              </div>
            </article>
            @empty
            <p class="sin-registros-espacios">
              {{ request()->hasAny(['buscar', 'estado', 'capacidad_min']) ? 'No hay espacios que coincidan con los filtros.' : 'No hay espacios registrados.' }}
            </p>
            @endforelse
          </div>

          <div class="paginacion">
            <small>Mostrando {{ $espacios->firstItem() ?? 0 }}–{{ $espacios->lastItem() ?? 0 }} de {{ $espacios->total() }} registros</small>
            <div>
              @if ($espacios->onFirstPage())
              <button class="btn-pag" disabled>Anterior</button>
              @else
              <a href="{{ $espacios->previousPageUrl() }}" class="btn-pag">Anterior</a>
              @endif
              @if ($espacios->hasMorePages())
              <a href="{{ $espacios->nextPageUrl() }}" class="btn-pag">Siguiente</a>
              @else
              <button class="btn-pag" disabled>Siguiente</button>
              @endif
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  @if (auth()->user()->tieneTipo('Administrador'))
  <!-- ===== VENTANA: REGISTRAR ESPACIO (HU-45) ===== -->
  @php $erroresRegistro = $errors->registrar; @endphp
  <dialog class="modal-plan" id="modal-registrar-espacio">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2>Registrar espacio</h2>
          <p>Administrador · Espacios del gimnasio</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form method="POST" action="{{ route('espacios.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="fila-plan una">
          <label class="campo-plan"><span>Nombre del espacio *</span>
            <input type="text" name="nombre_espacio" maxlength="50" value="{{ old('nombre_espacio') }}" placeholder="Ej. Sala de Yoga" required>
          </label>
        </div>
        <div class="fila-plan">
          <label class="campo-plan"><span>Capacidad (personas)</span>
            <input type="number" name="capacidad" min="1" step="1" value="{{ old('capacidad') }}" placeholder="Ej. 25">
          </label>
          <label class="campo-plan"><span>Estado inicial *</span>
            <select name="estado_espacio_id" required>
              @foreach ($estados->where('nombre_estado', '!=', 'Reservado') as $estado)
              <option value="{{ $estado->estado_espacio_id }}" @selected((string) old('estado_espacio_id') === (string) $estado->estado_espacio_id)>
                {{ $estado->nombre_estado }}
              </option>
              @endforeach
            </select>
          </label>
        </div>
        <div class="fila-plan una">
          <label class="campo-plan"><span>Foto (opcional · JPG, PNG o WEBP, máx. 2 MB)</span>
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
          </label>
        </div>
        <div class="fila-plan una">
          <label class="campo-plan"><span>Descripción</span>
            <input type="text" name="descripcion" maxlength="500" value="{{ old('descripcion') }}" placeholder="Equipamiento, ubicación u observaciones">
          </label>
        </div>

        @if ($erroresRegistro->any())
        <div class="mensaje-error">
          <ul>
            @foreach ($erroresRegistro->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Guardar espacio</button>
        </div>
      </form>
    </div>
  </dialog>

  <!-- ===== VENTANA: DESHABILITAR ESPACIO (HU-47) ===== -->
  <dialog class="modal-plan" id="modal-deshabilitar-espacio">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2>Deshabilitar espacio</h2>
          <p id="nombre-espacio-deshabilitar"></p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form method="POST" id="form-deshabilitar-espacio">
        @csrf
        @method('PATCH')
        <input type="hidden" name="accion" value="deshabilitar">
        @foreach (['buscar', 'estado', 'capacidad_min', 'page'] as $filtro)
        @if (request()->filled($filtro))
        <input type="hidden" name="{{ $filtro }}" value="{{ request($filtro) }}">
        @endif
        @endforeach
        <div class="fila-plan una">
          <label class="campo-plan"><span>Motivo *</span>
            <select name="motivo" required>
              @foreach (\App\Models\Espacio::ESTADOS_DESHABILITADOS as $motivo)
              <option value="{{ $motivo }}">{{ $motivo }}</option>
              @endforeach
            </select>
          </label>
        </div>
        <p class="nota-plan">Un espacio deshabilitado no se puede reservar. Si tiene reservas activas, primero debes cancelarlas.</p>
        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Deshabilitar</button>
        </div>
      </form>
    </div>
  </dialog>

  @if ($errors->registrar->any())
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => document.getElementById('modal-registrar-espacio')?.showModal());
  </script>
  @endif
  @endif
</body>

</html>
