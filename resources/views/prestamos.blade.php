<!DOCTYPE html>
<html lang="en">

<head>
    <!-- meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- estilos de usuarios y estilos del panel izquierdo y javascrpit del pop-out -->
    @vite(['resources/js/app.js','resources/js/pop_out.js','resources/js/devolucion-prestamo.js','resources/js/prestamo-categorias.js', 'resources/css/styles_prestamos.css', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css'])
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
    <!-- fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles_prestamos.css">
    <link rel="stylesheet" href="panel_izq.css">
    <link rel="stylesheet" href="nav_bar.css">
    <!-- titulo -->
    <title>Prestamos</title>
</head>

<body>
    <!-- ABRE FLEXBOX -->
    <div class="flexbox">
        <!-- ABRE PANEL IZQUIERDO -->
        <x-panel-izq />
        <!-- CIERRA PANEL IZQUIERDO -->
        <!-- ------------------------------------------------------------------------ -->
        <!-- ABRE PANEL DERECHO -->
        <div class="panel-dere">
            <!-- ABRE NAVBAR -->
            <x-nav_bar />
            <!-- CIERRA NAVBAR -->
            <!-- ABRE CONTENIDO PRINCIPAL -->
            <main class="contenido-principal">
                <!-- ABRE CONTENIDO PRESTAMOS -->
                <div class="contenido-prestamos">
                    {{-- Mensajes de confirmación --}}
                    @if (session('success'))
                    <div class="mensaje-exito" role="alert">
                        <span class="icono-exito">✓</span>
                        {{ session('success') }}
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="mensaje-error-general" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif
                    <!-- ABRE ENCABEZADO SECCION -->
                    <div class="encabezado-seccion">
                        <h1>Prestamos y devoluciones</h1>
                        <p>Controla unidades disponibles, responsables y devoluciones.</p>
                        <button type="button" id="abrir-dialogo">
                            <img width="24" height="24" src="https://img.icons8.com/android/24/plus.png" alt="plus" />
                            Registrar prestamo
                        </button>
                    </div>
                    <!-- CIERRA ENCABEZADO SECCION -->
                    <!-- ABRE VENTANA MODAL PARA REGISTRAR PRESTAMO -->
                    <!-- MODAL DE REGISTRO DE USUARIOS -->

                    <dialog id="modalRegistro" class="modal-registro">
                        <div class="modal-encabezado">
                            <div>
                                <h2>Registrar préstamo</h2>
                                <p>Ingresa la información para registrar un préstamo en SIGIP.</p>
                            </div>

                            <button
                                type="button"
                                id="btnCerrar"
                                class="btn-cerrar"
                                aria-label="Cerrar formulario">
                                &times;
                            </button>
                        </div>

                        <form id="formRegistro" method="POST" action="{{ route('prestamos.store') }}">
                            @csrf

                            <div class="row g-3">

                                <!-- TIPO DE DESTINATARIO -->
                                <div class="col-md-6">
                                    <label for="tipo_destinatario" class="form-label">
                                        Tipo de destinatario
                                    </label>

                                    <select
                                        class="form-select"
                                        id="tipo_destinatario"
                                        name="tipo_destinatario"
                                        required>
                                        <option value="" disabled {{ old('tipo_destinatario') ? '' : 'selected' }}>
                                            Seleccionar tipo
                                        </option>

                                        <option value="usuario" {{ old('tipo_destinatario') == 'usuario' ? 'selected' : '' }}>
                                            Usuario del gimnasio
                                        </option>

                                        <option value="instructor" {{ old('tipo_destinatario') == 'instructor' ? 'selected' : '' }}>
                                            Instructor
                                        </option>
                                    </select>
                                </div>

                                <!-- DOCUMENTO DEL DESTINATARIO -->
                                <div class="col-md-6">
                                    <label for="documento" class="form-label">
                                        Documento del destinatario
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="documento"
                                        name="documento"
                                        placeholder="Ingrese el número de documento"
                                        maxlength="12"
                                        value="{{ old('documento') }}"
                                        required>

                                    @error('documento')
                                    <small class="mensaje-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <!-- CATEGORÍA -->
                                <div class="col-md-6">
                                    <label for="categoria_implemento" class="form-label">
                                        Categoría de implementos
                                    </label>

                                    <select
                                        class="form-select"
                                        id="categoria_implemento"
                                        name="categoria_implemento">
                                        <option value="" selected>
                                            Todas las categorías
                                        </option>

                                        @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->categoria_implemento_id }}">
                                            {{ $categoria->nombre_categoria }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- IMPLEMENTOS DISPONIBLES -->
                                <div class="col-12">
                                    <label class="form-label">
                                        Seleccionar implementos
                                    </label>


                                    <div id="listaImplementos" class="lista-implementos">
                                        @error('implementos')
                                        <small class="mensaje-error">{{ $message }}</small>
                                        @enderror
                                        @forelse ($implementosDisponibles as $implemento)
                                        <label
                                            class="implemento-opcion"
                                            data-categoria="{{ $implemento->categoria_implemento_id ?? '' }}">

                                            <input
                                                type="checkbox"
                                                name="implementos[]"
                                                value="{{ $implemento->implemento_id }}">

                                            <span>
                                                <strong>{{ $implemento->nombre_implemento }}</strong>
                                            </span>
                                        </label>
                                        @empty
                                        <p class="mensaje-implementos" id="mensajeImplementos">
                                            No hay implementos disponibles para prestar actualmente.
                                        </p>
                                        @endforelse

                                    </div>

                                    <small class="text-muted">
                                        Puedes seleccionar varios implementos para un mismo préstamo.
                                    </small>
                                </div>

                            </div>

                            <!-- BOTONES -->
                            <div class="modal-acciones">
                                <button
                                    type="button"
                                    id="btnCancelar"
                                    class="btn-cancelar">
                                    Cancelar
                                </button>

                                <button
                                    type="submit"
                                    class="btn-guardar">
                                    Guardar préstamo
                                </button>
                            </div>
                        </form>
                    </dialog>
                    <!-- CIERRA POP OUT DE REGISTRAR PRESTAMO -->
                    <!-- ABRE DISPONIBILIDAD DE ELEMENTOS -->
                    <div class="prestamos-carrusel-contenedor">

    {{-- Flecha izquierda --}}
    <button
        type="button"
        class="flecha-carrusel"
        id="prestamos-anterior"
        aria-label="Ver categorías anteriores"
    >
        <i class="fas fa-chevron-left"></i>
    </button>

    {{-- Área visible del carrusel --}}
    <div class="disponibilidad-elementos">

        <div class="carrusel-categorias-track" id="prestamos-track">

            @forelse ($categorias as $categoria)

                <div class="tarjeta-metrica">

                    <div class="tarjeta-metrica-header">
                        <span class="titulo-implemento">
                            {{ $categoria->nombre_categoria }}
                        </span>
                    </div>

                    <div class="tarjeta-metrica-cuerpo">
                        <span class="cantidad">
                            {{ $categoria->ocupados }} / {{ $categoria->total }}
                        </span>

                        <span class="detalle">
                            {{ $categoria->disponibles }} disponibles
                            ·
                            {{ $categoria->ocupados }} ocupados
                        </span>
                    </div>

                </div>

            @empty

                <p class="mensaje-implementos">
                    No hay categorías de implementos registradas.
                </p>

            @endforelse

        </div>

    </div>

    {{-- Flecha derecha --}}
    <button
        type="button"
        class="flecha-carrusel"
        id="prestamos-siguiente"
        aria-label="Ver siguientes categorías"
    >
        <i class="fas fa-chevron-right"></i>
    </button>

</div>
<!-- ABRE PANEL DE PRESTAMOS -->
                    <div class="panel">

                         @if (
                            request()->filled('buscar') ||
                            request()->filled('tipo') ||
                            request()->filled('categoria') ||
                            request()->filled('estado') ||
                            request()->filled('periodo') ||
                            request()->filled('fecha')
                            )
                        <a href="{{ route('prestamos') }}" class="btn-limpiar">
                            <div class="pestanas">
                                <button type="button"> Todos los Préstamos</button>
                            </div>
                        </a>
                        @endif

                        <form method="GET" action="{{ route('prestamos') }}" class="busquedas" id="formFiltros">

                            <!-- BÚSQUEDA GENERAL -->
                            <div class="buscar">
                                <svg
                                    class="icono-busqueda"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-4-4"></path>
                                </svg>

                                <input
                                    type="search"
                                    class="barra-busqueda"
                                    name="buscar"
                                    value="{{ request('buscar') }}"
                                    placeholder="Buscar nombre, documento o implemento"
                                    aria-label="Buscar préstamos">
                            </div>

                            <!-- FILTRO POR TIPO -->
                            <div class="categoria">
                                <select name="tipo" class="combobox-categoria">
                                    <option value="">Todos los tipos</option>

                                    <option value="Instructor"
                                        {{ request('tipo') === 'Instructor' ? 'selected' : '' }}>
                                        Instructor
                                    </option>

                                    @foreach ($tiposUsuario as $tipoUsuario)
                                    <option value="{{ $tipoUsuario->nombre }}"
                                        {{ request('tipo') === $tipoUsuario->nombre ? 'selected' : '' }}>
                                        {{ $tipoUsuario->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- FILTRO POR CATEGORÍA -->
                            <div class="categoria">
                                <select name="categoria" class="combobox-categoria">
                                    <option value="">Todas las categorías</option>

                                    @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->categoria_implemento_id }}"
                                        {{ request('categoria') == $categoria->categoria_implemento_id ? 'selected' : '' }}>
                                        {{ $categoria->nombre_categoria }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- FILTRO POR ESTADO -->
                            <div class="estados">
                                <select name="estado" class="combobox-estado">
                                    <option value="">Todos los estados</option>

                                    <option value="activo"
                                        {{ request('estado') === 'activo' ? 'selected' : '' }}>
                                        Activos
                                    </option>

                                    <option value="inactivo"
                                        {{ request('estado') === 'inactivo' ? 'selected' : '' }}>
                                        Finalizados
                                    </option>
                                </select>
                            </div>
                            <!-- FILTRO POR PERIODO Y FECHA EXACTA -->
                            <div class="filtro-fecha">

                                <select name="periodo" class="combobox-estado">
                                    <option value="">Cualquier fecha</option>

                                    <option value="hoy"
                                        {{ request('periodo') === 'hoy' ? 'selected' : '' }}>
                                        Hoy
                                    </option>

                                    <option value="ultima_semana"
                                        {{ request('periodo') === 'ultima_semana' ? 'selected' : '' }}>
                                        Última semana
                                    </option>

                                    <option value="ultimo_mes"
                                        {{ request('periodo') === 'ultimo_mes' ? 'selected' : '' }}>
                                        Último mes
                                    </option>
                                </select>

                                <button
                                    type="button"
                                    class="btn-calendario"
                                    id="abrirCalendario"
                                    aria-label="Seleccionar fecha exacta">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        width="21"
                                        height="21"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round">

                                        <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                                        <path d="M16 3v4M8 3v4M3 11h18"></path>
                                    </svg>
                                </button>

                                <input
                                    type="date"
                                    name="fecha"
                                    id="fechaExacta"
                                    value="{{ request('fecha') }}"
                                    class="fecha-oculta">

                            </div>

                        </form>

                        <!-- CIERRA BUSQUEDAS -->
                        <!-- ABRE TABLA -->
                        <div class="tabla">
                            <table class="tabla-prestamos">
                                <thead>
                                    <tr>
                                        <th>Documento del responsable</th>
                                        <th>Tipo</th>
                                        <th>Fecha del préstamo</th>
                                        <th>Estado</th>
                                        <th>Ver detalles</th>
                                        <th>Registrar devolución</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($prestamos as $prestamo)

                                    <tr>
                                        {{-- Documento del responsable --}}
                                        <td>
                                            {{ $prestamo->documento_destinatario }}
                                        </td>

                                        {{-- Tipo de responsable --}}
                                        <td>
                                            {{ $prestamo->tipo_destinatario }}
                                        </td>

                                        {{-- Fecha y hora del préstamo --}}
                                        <td>
                                            <div>
                                                {{ \Carbon\Carbon::parse($prestamo->fecha_prestado)->format('d/m/Y') }}
                                            </div>

                                            <small>
                                                {{ \Carbon\Carbon::parse($prestamo->fecha_prestado)->format('h:i A') }}
                                            </small>
                                        </td>

                                        {{-- Estado del préstamo --}}
                                        <td>
                                            @if ($prestamo->activo)
                                            <span class="estado activo">Activo</span>
                                            @else
                                            <span class="estado inactivo">Finalizado</span>
                                            @endif
                                        </td>

                                        {{-- Consultar detalles --}}
                                        <td>
                                            <button
                                                type="button"
                                                class="ver-detalles"
                                                data-prestamo="{{ $prestamo->prestamo_id }}">
                                                Ver detalles
                                            </button>
                                        </td>

                                        {{-- Registrar devolución --}}
                                        <td>
                                            @if ($prestamo->activo)
                                            <button
                                                type="button"
                                                class="registrar-devolucion"
                                                data-prestamo="{{ $prestamo->prestamo_id }}"
                                                data-url="{{ route('prestamos.devolver', $prestamo->prestamo_id) }}"
                                                data-implementos='@json($prestamo->implementos->where("estado_devolucion", "Pendiente")->values())'
                                                data-destinatario="{{ trim($prestamo->nombre . ' ' . $prestamo->apellido) }}"
                                                data-tipo="{{ $prestamo->tipo_destinatario }}"
                                                data-fecha="{{ \Carbon\Carbon::parse($prestamo->fecha_prestado)->format('d/m/Y h:i A') }}"
                                                data-cantidad="{{ $prestamo->cantidad_implementos }}">
                                                Registrar devolución
                                            </button>
                                            @else
                                            <button type="button" class="prestamo-finalizado" disabled>
                                                Préstamo finalizado
                                            </button>
                                            @endif
                                        </td>
                                    </tr>

                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            No hay préstamos registrados.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <!-- CIERRA TABLA -->
                            <!-- ABRE POP OUT DE REGISTRAR DEVOLUCION -->
                            <dialog id="modalDevolucion" class="modal-registro">
                                <!-- ENCABEZADO -->
                                <div class="modal-encabezado">
                                    <div>
                                        <h2>Registrar devolución</h2>
                                        <p class="modal-subtitulo" id="infoDestinatario"></p>
                                    </div>

                                    <button type="button" id="btnCerrarDev" class="btn-cerrar" aria-label="Cerrar formulario">
                                        &times;
                                    </button>
                                </div>

                                <div class="modal-info-resumen">
                                    <div class="resumen-col resumen-progreso">
                                        <div class="progreso-encabezado">
                                            <span class="label">Progreso de devolución</span>
                                            <span class="valor" id="progresoTexto">0 / 0</span>
                                        </div>

                                        <div
                                            class="barra-progreso"
                                            role="progressbar"
                                            aria-label="Progreso de devolución"
                                            aria-valuemin="0"
                                            aria-valuemax="0"
                                            aria-valuenow="0"
                                            id="barraProgreso">
                                            <div class="barra-progreso-llenado" id="progresoLlenado"></div>
                                        </div>

                                        <span class="progreso-detalle" id="progresoDetalle">
                                            0 de 0 implementos devueltos
                                        </span>
                                    </div>

                                    <div class="resumen-col">
                                        <span class="label">Fecha del préstamo</span>
                                        <span class="valor" id="fechaPrestamo"></span>
                                    </div>
                                </div>
                                <!-- FORMULARIO -->
                                <form id="formDevolucion" method="POST">
                                    @csrf

                                    <div id="listaDevoluciones" class="row g-3">
                                        <!-- JavaScript agregará aquí los implementos pendientes -->
                                    </div>

                                    <div class="modal-acciones">
                                        <button type="button" id="btnCancelarDev" class="btn-cancelar">
                                            Cancelar
                                        </button>

                                        <button type="submit" class="btn-guardar">
                                            Confirmar devolución
                                        </button>
                                    </div>
                                </form>
                            </dialog>
                            <!-- CIERRA POP OUT DE REGISTRAR PRESTAMO -->
                        </div>
                        <!-- ABRE PAGINACION -->
                        <div class="paginacion">
                            <p class="texto-registros">
                                Mostrando
                                {{ $prestamos->firstItem() ?? 0 }}-{{ $prestamos->lastItem() ?? 0 }}
                                de {{ $prestamos->total() }} registros
                            </p>

                            <nav aria-label="Navegación de páginas">
                                <ul class="pagination">
                                    {{-- Página anterior --}}
                                    <li class="{{ $prestamos->onFirstPage() ? 'disabled' : '' }}">
                                        @if ($prestamos->onFirstPage())
                                        <span class="page-link pagina-anterior">&laquo; Anterior</span>
                                        @else
                                        <a href="{{ $prestamos->previousPageUrl() }}"
                                            class="page-link pagina-anterior">
                                            &laquo; Anterior
                                        </a>
                                        @endif
                                    </li>

                                    {{-- Números de página --}}
                                    @for (
                                    $pagina = max(1, $prestamos->currentPage() - 2);
                                    $pagina <= min($prestamos->lastPage(), $prestamos->currentPage() + 2);
                                        $pagina++
                                        )
                                        <li>
                                            <a href="{{ $prestamos->url($pagina) }}"
                                                class="page-link {{ $prestamos->currentPage() == $pagina ? 'active' : '' }}"
                                                @if ($prestamos->currentPage() == $pagina)
                                                aria-current="page"
                                                @endif>
                                                {{ $pagina }}
                                            </a>
                                        </li>
                                        @endfor

                                        {{-- Página siguiente --}}
                                        <li class="{{ !$prestamos->hasMorePages() ? 'disabled' : '' }}">
                                            @if ($prestamos->hasMorePages())
                                            <a href="{{ $prestamos->nextPageUrl() }}"
                                                class="page-link pagina-siguiente">
                                                Siguiente &raquo;
                                            </a>
                                            @else
                                            <span class="page-link pagina-siguiente">
                                                Siguiente &raquo;
                                            </span>
                                            @endif
                                        </li>
                                </ul>
                            </nav>
                        </div>
                        <!-- CIERRA PAGINACION -->
                    </div>
                </div>
                <!-- CIERRA PANEL DE PRESTAMOS -->
        </div>
        <!-- CIERRA CONTENIDO PRESTAMOS -->
        </main>
        <!-- CIERRA CONTENIDO PRINICPAL -->
    </div>
    <!-- ABRE PANEL IZQUIERDO -->
    </div>
    <!-- CIERRA FLEXBOX -->
    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalRegistro');

            if (modal) {
                modal.showModal();
            }
        });
    </script>
    @endif
</body>

</html>