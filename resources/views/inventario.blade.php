<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGIP | Inventario</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css', 'resources/js/registrar-inventario.js', 'resources/js/inventario-tarjetas.js', 'resources/js/sigip.js'])
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
          <div class="acciones-titulo">
            <a href="{{ route('inventario.exportar', request()->query()) }}" class="btn-exportar">Exportar PDF</a>
            @if (auth()->user()->tieneTipo('Administrador'))
            <button class="btn-primario" id="abrir-registrar-elemento">
              + Registrar elemento
            </button>
            @endif
          </div>
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
        {{-- HU-159: categorías por debajo del stock mínimo --}}
        @if ($stockBajo->isNotEmpty())
        <div class="alerta-stock">
          <strong>Stock por debajo del mínimo:</strong>
          @foreach ($stockBajo as $categoria)
          {{ $categoria->nombre_categoria }} ({{ $categoria->activos }} de {{ $categoria->stock_minimo }}){{ $loop->last ? '.' : ',' }}
          @endforeach
        </div>
        @endif

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

          {{-- HU-41: filtros según la pestaña.
               Todos: búsqueda, categoría y cantidad (aplican a máquinas e implementos).
               Máquinas: búsqueda, categoría, ubicación, estado de máquina y cantidad.
               Implementos: búsqueda, categoría, estado de implemento, stock mínimo y cantidad. --}}
          <form class="filtros filtros-inventario" method="GET" action="{{ route('inventario.index') }}" id="form-filtros-inventario">
            @if ($tipo !== 'todos')
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            @endif
            <input
              type="search"
              name="buscar"
              id="buscar-inventario"
              value="{{ request('buscar') }}"
              placeholder="{{ $tipo === 'maquina' ? 'Buscar máquina por código, nombre, categoría o ubicación...' : ($tipo === 'implemento' ? 'Buscar implemento por código, nombre o categoría...' : 'Buscar por código, nombre o categoría...') }}"
              autocomplete="off">

            <select name="categoria" data-autoenviar aria-label="Categoría">
              <option value="">Categoría: todas</option>
              @if ($tipo !== 'implemento')
              <optgroup label="Máquinas">
                @foreach ($categoriasMaquina as $categoria)
                <option value="m:{{ $categoria->categoria_maquina_id }}" @selected(request('categoria') === 'm:' . $categoria->categoria_maquina_id)>{{ $categoria->nombre_categoria }}</option>
                @endforeach
              </optgroup>
              @endif
              @if ($tipo !== 'maquina')
              <optgroup label="Implementos">
                @foreach ($categoriasImplemento as $categoria)
                <option value="i:{{ $categoria->categoria_implemento_id }}" @selected(request('categoria') === 'i:' . $categoria->categoria_implemento_id)>{{ $categoria->nombre_categoria }}</option>
                @endforeach
              </optgroup>
              @endif
            </select>

            @if ($tipo === 'maquina')
            <select name="espacio" data-autoenviar aria-label="Ubicación">
              <option value="">Ubicación: todas</option>
              @foreach ($espacios as $espacio)
              <option value="{{ $espacio->espacio_id }}" @selected((string) request('espacio') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
              @endforeach
            </select>
            @endif

            @if ($tipo !== 'todos')
            <select name="estado" data-autoenviar aria-label="Estado">
              <option value="">Estado: todos</option>
              @foreach ($tipo === 'maquina' ? $estadosMaquina : $estadosImplemento as $estadoOpcion)
              <option value="{{ $estadoOpcion->nombre_estado }}" @selected(request('estado') === $estadoOpcion->nombre_estado)>{{ $estadoOpcion->nombre_estado }}</option>
              @endforeach
            </select>
            @endif

            @if ($tipo === 'implemento')
            <select name="stock" data-autoenviar aria-label="Stock">
              <option value="">Stock: todos</option>
              <option value="bajo" @selected(request('stock') === 'bajo')>Bajo el mínimo</option>
              <option value="suficiente" @selected(request('stock') === 'suficiente')>Stock suficiente</option>
            </select>
            @endif

            <label class="filtro-cantidad">Cantidad mín.
              <input type="number" name="cantidad_min" min="0" step="1" value="{{ request('cantidad_min') }}" placeholder="0">
            </label>
            <label class="filtro-cantidad">Cantidad máx.
              <input type="number" name="cantidad_max" min="0" step="1" value="{{ request('cantidad_max') }}" placeholder="—">
            </label>

            <button type="submit" class="btn-borde">Filtrar</button>
            @if (request()->hasAny(['buscar', 'categoria', 'espacio', 'estado', 'stock', 'cantidad_min', 'cantidad_max']))
            <a href="{{ route('inventario.index', $tipo !== 'todos' ? ['tipo' => $tipo] : []) }}" class="btn-borde">Limpiar</a>
            @endif
          </form>

          {{-- Una tarjeta por categoría: foto, cantidad de unidades y barra por estado. --}}
          <div class="inventario-grid">
            @forelse ($paginacion as $categoria)
            @php
              $unidad = $categoria->tipo === 'maquina' ? ($categoria->total === 1 ? 'máquina' : 'máquinas') : ($categoria->total === 1 ? 'implemento' : 'implementos');
            @endphp
            <article class="tarjeta-categoria {{ $categoria->bajo_stock ? 'bajo-stock' : '' }}">
              <div class="tarjeta-categoria-foto">
                @if ($categoria->imagen)
                <img src="{{ $categoria->imagen }}" alt="Foto de {{ $categoria->nombre }}" loading="lazy">
                @else
                <div class="tarjeta-categoria-sin-foto" aria-hidden="true">
                  @if ($categoria->tipo === 'maquina')
                  <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="14" width="18" height="5" rx="1.5"/><path d="M6 14V8a2 2 0 0 1 2-2h3l3 8"/><path d="M17 14V9"/></svg>
                  @else
                  <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 6.5h11M6.5 17.5h11"/><rect x="2" y="8" width="4" height="8" rx="1"/><rect x="18" y="8" width="4" height="8" rx="1"/><path d="M12 6.5v11"/></svg>
                  @endif
                </div>
                @endif
                <span class="badge tarjeta-categoria-tipo {{ $categoria->tipo === 'maquina' ? 'morado' : 'gris' }}">{{ $categoria->tipo === 'maquina' ? 'Máquinas' : 'Implementos' }}</span>
              </div>

              <div class="tarjeta-categoria-cuerpo">
                <div class="tarjeta-categoria-titulo">
                  <h3 title="{{ $categoria->nombre }}">{{ $categoria->nombre }}</h3>
                  <span class="tarjeta-categoria-total"><strong>{{ $categoria->total }}</strong> {{ $unidad }}</span>
                </div>

                @if ($categoria->total > 0)
                {{-- Barra apilada: cuántas unidades hay en cada estado. --}}
                <div class="barra-estados" role="img" aria-label="{{ collect($categoria->estados)->where('total', '>', 0)->map(fn ($e) => $e['total'] . ' ' . mb_strtolower($e['nombre']))->implode(', ') }}">
                  @foreach ($categoria->estados as $estado)
                  @if ($estado['total'] > 0)
                  <span class="segmento estado-{{ $estado['clase'] }}" style="flex-grow: {{ $estado['total'] }}" title="{{ $estado['nombre'] }}: {{ $estado['total'] }}"></span>
                  @endif
                  @endforeach
                </div>
                <ul class="leyenda-estados">
                  @foreach ($categoria->estados as $estado)
                  @if ($estado['total'] > 0)
                  <li><span class="punto estado-{{ $estado['clase'] }}"></span>{{ $estado['nombre'] }} <strong>{{ $estado['total'] }}</strong></li>
                  @endif
                  @endforeach
                </ul>
                @else
                <p class="tarjeta-categoria-vacia">Sin unidades registradas.</p>
                @endif

                @if ($categoria->bajo_stock)
                <p class="tarjeta-categoria-alerta">Stock por debajo del mínimo ({{ $categoria->stock_minimo }})</p>
                @endif
              </div>

              <div class="tarjeta-categoria-acciones">
                <button type="button" class="btn-icono" data-ver-categoria="{{ json_encode(['nombre' => $categoria->nombre, 'tipo' => $categoria->tipo, 'unidades' => $categoria->unidades], JSON_UNESCAPED_UNICODE) }}" title="Ver unidades" aria-label="Ver las unidades de {{ $categoria->nombre }}">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                @if (auth()->user()->tieneTipo('Administrador'))
                <button type="button" class="btn-icono" data-editar-categoria="{{ json_encode(['accion' => route('inventario.categoria.update', [$categoria->tipo, $categoria->id]), 'tipo' => $categoria->tipo, 'nombre' => $categoria->nombre, 'descripcion' => $categoria->descripcion, 'stock_minimo' => $categoria->stock_minimo, 'imagen' => $categoria->imagen], JSON_UNESCAPED_UNICODE) }}" title="Editar categoría" aria-label="Editar la categoría {{ $categoria->nombre }}">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </button>
                @endif
              </div>
            </article>
            @empty
            <p class="sin-registros-espacios">
              {{ request()->hasAny(['buscar', 'categoria', 'espacio', 'estado', 'stock', 'cantidad_min', 'cantidad_max']) ? 'No hay elementos que coincidan con los filtros.' : 'No hay categorías registradas en el inventario.' }}
            </p>
            @endforelse
          </div>

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

        {{-- Pop-up "Ver": unidades de la categoría, cada una con su detalle (ver y editar). --}}
        <dialog class="modal-plan modal-registros" id="modal-unidades-categoria" aria-labelledby="titulo-unidades-categoria">
          <div class="modal-plan-cuerpo">
            <div class="modal-plan-cab">
              <div>
                <h2 id="titulo-unidades-categoria">Unidades</h2>
                <p id="subtitulo-unidades-categoria"></p>
              </div>
              <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
            </div>
            <div class="tabla-desplazable">
              <table class="tabla">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th class="solo-maquina">Ubicación</th>
                    <th class="solo-maquina">Últ. mantenimiento</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="cuerpo-unidades-categoria"></tbody>
              </table>
            </div>
          </div>
        </dialog>

        @if (auth()->user()->tieneTipo('Administrador'))
        {{-- Pop-up "Editar": nombre, descripción, stock mínimo y foto de la categoría. --}}
        <dialog class="modal-plan" id="modal-editar-categoria" aria-labelledby="titulo-editar-categoria">
          <div class="modal-plan-cuerpo">
            <div class="modal-plan-cab">
              <div>
                <h2 id="titulo-editar-categoria">Editar categoría</h2>
                <p>La foto se muestra en la tarjeta de la categoría.</p>
              </div>
              <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
            </div>
            <form method="POST" id="form-editar-categoria" action="{{ old('_accion_categoria') }}" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="_accion_categoria" id="editar-categoria-accion" value="{{ old('_accion_categoria') }}">
              <div class="campo-foto">
                <div class="campo-foto-vista" id="editar-categoria-vista"><span class="avatar-iniciales">+</span></div>
                <div class="campo-foto-entrada">
                  <label class="campo-plan"><span>Foto (JPG, PNG o WEBP, máx. 2 MB)</span>
                    <input type="file" name="imagen" id="editar-categoria-imagen" accept="image/jpeg,image/png,image/webp">
                  </label>
                  <label class="quitar-foto-categoria" id="editar-categoria-quitar" hidden><input type="checkbox" name="quitar_imagen" value="1"> Quitar la foto actual</label>
                </div>
              </div>
              <div class="fila-plan una">
                <label class="campo-plan"><span>Nombre de la categoría *</span>
                  <input type="text" name="nombre_categoria" id="editar-categoria-nombre" maxlength="45" value="{{ old('nombre_categoria') }}" required>
                </label>
              </div>
              <div class="fila-plan una">
                <label class="campo-plan"><span>Descripción</span>
                  <input type="text" name="descripcion" id="editar-categoria-descripcion" maxlength="500" value="{{ old('descripcion') }}">
                </label>
              </div>
              <div class="fila-plan una" id="editar-categoria-fila-stock">
                <label class="campo-plan"><span>Stock mínimo (implementos disponibles o prestados) *</span>
                  <input type="number" name="stock_minimo" id="editar-categoria-stock" min="0" step="1" value="{{ old('stock_minimo') }}">
                </label>
              </div>
              @if ($errors->categoria->any())
              <div class="invalid-feedback d-block">
                @foreach ($errors->categoria->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
              </div>
              @endif
              <div class="modal-plan-acciones">
                <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
                <button type="submit" class="btn-primario">Guardar cambios</button>
              </div>
            </form>
          </div>
        </dialog>
        @endif

      </main>
    </div>
  </div>
</body>

</html>