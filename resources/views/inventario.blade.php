<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGIP | Inventario</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css', 'resources/js/registrar-inventario.js'])
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">

</head>

<body>
  <div class="flexbox">
    <x-panel-izq />
    <div class="contenido">
      <x-nav_bar />
      <main class="pagina">
        <div class="pagina-titulo">
          <div>
            <h1>Inventario</h1>
            <p>Catálogo de máquinas e implementos. Las máquinas no son unidades prestables.</p>
          </div>
          <button class="btn-primario" id="abrir-registrar-elemento">
            + Registrar elemento
          </button>
        </div>
        {{-- Mensajes de confirmación y errores --}}
        @if (session('success'))
        <p class="mensaje-exito">
          {{ session('success') }}
        </p>
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
        <!-- ===== VENTANA: REGISTRAR ELEMENTO ===== -->
        <dialog class="modal-plan" id="modal-registrar-elemento">
          <div class="modal-plan-cuerpo">
            <div class="modal-plan-cab">
              <div>
                <h2>Registrar elemento</h2>
                <p>Administrador · Catálogo de máquinas e implementos</p>
              </div>
              <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
            </div>

            <form action="{{ route('inventario.store') }}" method="POST">
              @csrf
              <div class="fila-plan una">
                <label class="campo-plan">
                  <span>Tipo de elemento *</span>
                  <select name="tipo" id="tipo-elemento" required>
                    <option value="maquina">Máquina</option>
                    <option value="implemento">Implemento</option>
                  </select>
                </label>
              </div>
              <div class="fila-plan una">
                <label class="campo-plan">
                  <span>Categoría *</span>
                  <select name="categoria_id" id="categoria-maquina" required>
                    <option value="">Selecciona una categoría</option>
                    @foreach ($categoriasMaquina as $categoria)
                    <option value="{{ $categoria->categoria_maquina_id }}">
                      {{ $categoria->nombre_categoria }}
                    </option>
                    @endforeach
                  </select>
                  <select name="categoria_id" id="categoria-implemento" disabled hidden>
                    <option value="">Selecciona una categoría</option>
                    @foreach ($categoriasImplemento as $categoria)
                    <option value="{{ $categoria->categoria_implemento_id }}">
                      {{ $categoria->nombre_categoria }}
                    </option>
                    @endforeach
                  </select>
                </label>
              </div>
              <div class="fila-plan una" id="fila-ubicacion">
                <label class="campo-plan">
                  <span>Ubicación *</span>
                  <select name="espacio_id" id="espacio-elemento" required>
                    <option value="">Selecciona un espacio</option>
                    @foreach ($espacios as $espacio)
                    <option value="{{ $espacio->espacio_id }}">
                      {{ $espacio->nombre_espacio }}
                    </option>
                    @endforeach
                  </select>
                </label>
              </div>
              <div class="fila-plan una">
                <label class="campo-plan">
                  <span>Estado operativo *</span>
                  <select name="estado_id" id="estado-maquina" required>
                    <option value="">Selecciona un estado</option>
                    @foreach ($estadosMaquina as $estado)
                    <option value="{{ $estado->estado_maquina_id }}">
                      {{ $estado->nombre_estado }}
                    </option>
                    @endforeach
                  </select>
                  <select name="estado_id" id="estado-implemento" disabled hidden>
                    <option value="">Selecciona un estado</option>
                    @foreach ($estadosImplementoRegistro as $estado)
                    <option value="{{ $estado->estado_implemento_id }}">
                      {{ $estado->nombre_estado }}
                    </option>
                    @endforeach
                  </select>
                </label>
              </div>
              <div class="fila-plan una">
                <label class="campo-plan">
                  <span>Observaciones</span>
                  <input
                    type="text"
                    name="observaciones"
                    placeholder="Descripción u observaciones del elemento">
                </label>
              </div>
              <p class="nota-plan">
                Se registrará una unidad individual. El nombre y el código
                se generarán automáticamente.
              </p>
              <div class="modal-plan-acciones">
                <button type="button" class="btn-borde" data-cerrar>
                  Cancelar
                </button>
                <button type="submit" class="btn-primario">
                  Guardar elemento
                </button>
              </div>
            </form>
          </div>
        </dialog>
        <div class="resumen">
          <span class="chip morado">
            {{ $totalMaquinas }} máquinas
          </span>
          <span class="chip verde">
            {{ $totalDisponibles }} implementos disponibles
          </span>
          <span class="chip amarillo">
            {{ $totalPrestados }} prestados
          </span>
          <span class="chip rojo">
            {{ $totalAveriados }} averiados
          </span>
          <span class="resumen-texto">
            {{ $totalPerdidos }} perdidos
          </span>
        </div>
        <section class="tarjeta">
          <div class="pestanas">
            <a href="{{ route('inventario.index') }}" class="{{ $tipo === 'todos' ? 'activa' : '' }}">
              Todos los elementos ({{ $totalMaquinas + $totalImplementos }})
            </a>
            <a href="{{ route('inventario.index', ['tipo' => 'maquina']) }}" class="{{ $tipo === 'maquina' ? 'activa' : '' }}">
              Máquinas ({{ $totalMaquinas }})
            </a>
            <a href="{{ route('inventario.index', ['tipo' => 'implemento']) }}" class="{{ $tipo === 'implemento' ? 'activa' : '' }}">
              Implementos ({{ $totalImplementos }} unidades)
            </a>
          </div>

          {{-- HU-41: filtros aplicados en el servidor --}}
          <form class="filtros" method="GET" action="{{ route('inventario.index') }}" id="form-filtros-inventario">
            @if ($tipo !== 'todos')
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            @endif
            <input
              type="search"
              name="buscar"
              id="buscar-inventario"
              value="{{ request('buscar') }}"
              placeholder="Buscar por código, nombre, categoría, estado o ubicación..."
              autocomplete="off">
            <select name="categoria" data-autoenviar>
              <option value="">Categoría: todas</option>
              <optgroup label="Máquinas">
                @foreach ($categoriasMaquina as $categoria)
                <option value="m:{{ $categoria->categoria_maquina_id }}" @selected(request('categoria') === 'm:' . $categoria->categoria_maquina_id)>
                  {{ $categoria->nombre_categoria }}
                </option>
                @endforeach
              </optgroup>
              <optgroup label="Implementos">
                @foreach ($categoriasImplemento as $categoria)
                <option value="i:{{ $categoria->categoria_implemento_id }}" @selected(request('categoria') === 'i:' . $categoria->categoria_implemento_id)>
                  {{ $categoria->nombre_categoria }}
                </option>
                @endforeach
              </optgroup>
            </select>
            <select name="espacio" data-autoenviar>
              <option value="">Ubicación: todas</option>
              @foreach ($espacios as $espacio)
              <option value="{{ $espacio->espacio_id }}" @selected((string) request('espacio') === (string) $espacio->espacio_id)>
                {{ $espacio->nombre_espacio }}
              </option>
              @endforeach
            </select>
            <select name="estado" data-autoenviar>
              <option value="">Estado: todos</option>
              @foreach ($nombresEstado as $nombreEstado)
              <option value="{{ $nombreEstado }}" @selected(request('estado') === $nombreEstado)>
                {{ $nombreEstado }}
              </option>
              @endforeach
            </select>
            @if (request()->hasAny(['buscar', 'categoria', 'espacio', 'estado']))
            <a href="{{ route('inventario.index') }}" class="btn-borde">Limpiar filtros</a>
            @endif
          </form>

          <table class="tabla {{ $tipo === 'maquina' ? 'solo-maquinas' : '' }}">
            <thead>
              <tr>
                <th>Código / nombre</th>
                <th>Categoría</th>
                <th class="col-ubicacion">Ubicación</th>
                <th>Estado operativo</th>
                <th class="col-mantenimiento">Últ. mantenimiento</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {{-- Máquinas --}}
              @foreach ($maquinas as $maquina)
              <tr data-tipo="maquina">
                <td>
                  MAQ-{{ $maquina->maquina_id }} ·
                  {{ $maquina->nombre_maquina }}
                </td>

                <td>{{ $maquina->nombre_categoria }}</td>

                <td class="col-ubicacion">
                  {{ $maquina->nombre_espacio ?? 'Sin ubicación' }}
                </td>

                <td>{{ $maquina->nombre_estado }}</td>

                <td class="col-mantenimiento">
                  @if ($maquina->ultima_fecha)
                  {{ \Carbon\Carbon::parse($maquina->ultima_fecha)->format('d/m/Y') }}
                  @else
                  Sin mantenimiento
                  @endif
                </td>

                <td class="iconos">
                  👁 ✎ ⋯
                </td>
              </tr>
              @endforeach

              {{-- Implementos --}}
              @foreach ($implementos as $implemento)
              <tr data-tipo="implemento">
                <td>
                  IMP-{{ $implemento->implemento_id }} ·
                  {{ $implemento->nombre_implemento }}
                </td>

                <td>{{ $implemento->nombre_categoria }}</td>

                <td class="col-ubicacion">Sin ubicación</td>

                <td>{{ $implemento->nombre_estado }}</td>

                <td class="col-mantenimiento">No aplica</td>

                <td class="iconos">
                  👁 ✎ ⋯
                </td>
              </tr>
              @endforeach

              @if ($paginacion->total() === 0)
              <tr>
                <td colspan="6">
                  {{ request()->hasAny(['buscar', 'categoria', 'espacio', 'estado']) ? 'No hay elementos que coincidan con los filtros.' : 'No hay elementos registrados en el inventario.' }}
                </td>
              </tr>
              @endif
            </tbody>
          </table>

          <div class="paginacion">
            <small>
              Mostrando
              {{ $paginacion->firstItem() ?? 0 }}–{{ $paginacion->lastItem() ?? 0 }}
              de {{ $paginacion->total() }} registros
            </small>

            <div>
              {{-- Botón anterior --}}
              @if ($paginacion->onFirstPage())
              <button type="button" class="btn-pag" disabled>
                Anterior
              </button>
              @else
              <a href="{{ $paginacion->previousPageUrl() }}" class="btn-pag">
                Anterior
              </a>
              @endif

              {{-- Números de página --}}
              @foreach ($paginacion->getUrlRange(
              max(1, $paginacion->currentPage() - 2),
              min($paginacion->lastPage(), $paginacion->currentPage() + 2)
              ) as $pagina => $url)
              <a
                href="{{ $url }}"
                class="btn-pag {{ $paginacion->currentPage() == $pagina ? 'actual' : '' }}">
                {{ $pagina }}
              </a>
              @endforeach

              {{-- Botón siguiente --}}
              @if ($paginacion->hasMorePages())
              <a href="{{ $paginacion->nextPageUrl() }}" class="btn-pag">
                Siguiente
              </a>
              @else
              <button type="button" class="btn-pag" disabled>
                Siguiente
              </button>
              @endif
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>
</body>

</html>