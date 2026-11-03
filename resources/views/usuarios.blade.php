<!DOCTYPE html>
<html lang="es">

<head>
    <!-- meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- estilos de usuarios y estilos del panel izquierdo y javascrpit del pop-out -->
    @vite(['resources/js/app.js', 'resources/js/pop_out.js'])
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <!-- fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <!-- titulo -->
    <title>Usuarios</title>
</head>

<body>
    <!-- crear flexbox para mantener repartidos uniformemente el panel derecho del izquierdo -->
    <div class="flexbox">
        <!-- --------------------ABRE PANEL IZQUIERO------------------------ -->
        <x-panel-izq />
        <!-- -------------------------------------------- -->
        <!-- CIERRA PANEL IZQUIERDO -->
        <!-- -------------------------------------------------------------------------------------------- -->
        <!-- ABRE PANEL DERECHO -->
        <div class="panel-dere">
            <!-- ABRE NAVBAR -->
            <x-nav_bar />
            <!-- CIERRA NAVBAR -->
            <!-- ABRE CONTENIDO DE USUARIOS -->
            <main class="contenido-principal">
                <div class="contenido-users">
                    @if (session('success'))
                    <div class="alerta-exito" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif
                    <div class="encabezado-seccion">
                        <h1>Usuarios</h1>
                        <p>Consulta y administra cuentas, roles y condiciones de la comunidad.</p>
                        <button type="button" id="abrir-dialogo">
                            <img width="24" height="24" src="https://img.icons8.com/external-thin-kawalan-studio/24/external-user-plus-users-thin-kawalan-studio.png" alt="external-user-plus-users-thin-kawalan-studio" />
                            Registrar usuario
                        </button>
                    </div>

                    <!-- MODAL DE REGISTRO DE USUARIOS -->
                    <dialog id="modalRegistro" class="modal-registro">
                        <div class="modal-encabezado">
                            <div>
                                <h2>Registrar usuario</h2>
                                <p>Ingresa la información para registrar un usuario en SIGIP.</p>
                            </div>
                            <button
                                type="button"
                                id="btnCerrar"
                                class="btn-cerrar"
                                aria-label="Cerrar formulario">
                                &times;
                            </button>
                        </div>

                        <form id="formRegistro" method="POST" action="{{ route('usuarios.store') }}">
                            @csrf

                            <div class="row g-3">

                                <!-- NOMBRE -->
                                <div class="col-md-6">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input
                                        type="text"
                                        class="form-control @error('nombre') is-invalid @enderror"
                                        id="nombre"
                                        name="nombre"
                                        placeholder="Ingrese el nombre"
                                        autocomplete="given-name"
                                        maxlength="50"
                                        value="{{ old('nombre') }}"
                                        required>

                                    @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- APELLIDO -->
                                <div class="col-md-6">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input
                                        type="text"
                                        class="form-control @error('apellido') is-invalid @enderror"
                                        id="apellido"
                                        name="apellido"
                                        placeholder="Ingrese el apellido"
                                        autocomplete="family-name"
                                        maxlength="50"
                                        value="{{ old('apellido') }}"
                                        required>

                                    @error('apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- TIPO DE DOCUMENTO -->
                                <div class="col-md-6">
                                    <label for="tipo_documento_id" class="form-label">
                                        Tipo de documento
                                    </label>

                                    <select
                                        class="form-select @error('tipo_documento_id') is-invalid @enderror"
                                        name="tipo_documento_id"
                                        id="tipo_documento_id"
                                        required>

                                        <option value="" disabled {{ old('tipo_documento_id') ? '' : 'selected' }}>
                                            Seleccionar tipo de documento
                                        </option>

                                        @foreach ($tiposDocumento as $tipoDocumento)
                                        <option
                                            value="{{ $tipoDocumento->tipo_documento_id }}"
                                            {{ old('tipo_documento_id') == $tipoDocumento->tipo_documento_id ? 'selected' : '' }}>
                                            {{ $tipoDocumento->nombre_tipo_documento }}
                                        </option>
                                        @endforeach
                                    </select>

                                    @error('tipo_documento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- DOCUMENTO -->
                                <div class="col-md-6">
                                    <label for="documento" class="form-label">
                                        Número de documento
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control @error('documento') is-invalid @enderror"
                                        id="documento"
                                        name="documento"
                                        placeholder="Ingrese el documento"
                                        maxlength="11"
                                        minlength="8"
                                        pattern="[0-9]{8,11}"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        title="El documento debe contener entre 8 y 11 números, sin letras ni espacios."
                                        value="{{ old('documento') }}"
                                        required>

                                    @error('documento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- GÉNERO -->
                                <div class="col-md-6">
                                    <label for="genero_id" class="form-label">Género</label>

                                    <select
                                        class="form-select @error('genero_id') is-invalid @enderror"
                                        name="genero_id"
                                        id="genero_id"
                                        required>

                                        <option value="" disabled {{ old('genero_id') ? '' : 'selected' }}>
                                            Seleccionar género
                                        </option>

                                        @foreach ($generos as $genero)
                                        <option
                                            value="{{ $genero->genero_id }}"
                                            {{ old('genero_id') == $genero->genero_id ? 'selected' : '' }}>
                                            {{ $genero->nombre_genero }}
                                        </option>
                                        @endforeach
                                    </select>

                                    @error('genero_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- EPS -->
                                <div class="col-md-6">
                                    <label for="eps_id" class="form-label">EPS</label>

                                    <select
                                        class="form-select @error('eps_id') is-invalid @enderror"
                                        name="eps_id"
                                        id="eps_id">

                                        <option value="">Sin EPS</option>

                                        @foreach ($eps as $item)
                                        <option
                                            value="{{ $item->eps_id }}"
                                            {{ old('eps_id') == $item->eps_id ? 'selected' : '' }}>
                                            {{ $item->nombre_eps }}
                                        </option>
                                        @endforeach
                                    </select>

                                    @error('eps_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- CORREO -->
                                <div class="col-md-6">
                                    <label for="correo" class="form-label">
                                        Correo electrónico
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control @error('correo') is-invalid @enderror"
                                        id="correo"
                                        name="correo"
                                        placeholder="usuario@dominio.com"
                                        autocomplete="email"
                                        maxlength="80"
                                        value="{{ old('correo') }}"
                                        pattern="[A-Za-z0-9]+([._%+-][A-Za-z0-9]+)*@[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?(\.[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?)*\.[A-Za-z]{2,}"
                                        title="Ingrese un correo válido, por ejemplo usuario@dominio.com"
                                        required>

                                    @error('correo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- TELÉFONO -->
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>

                                    <input
                                        type="tel"
                                        class="form-control @error('telefono') is-invalid @enderror"
                                        id="telefono"
                                        name="telefono"
                                        placeholder="Ingrese el teléfono"
                                        autocomplete="tel"
                                        maxlength="11"
                                        value="{{ old('telefono') }}">

                                    @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- FECHA DE NACIMIENTO -->
                                <div class="col-md-6">
                                    <label for="fecha_nacimiento" class="form-label">
                                        Fecha de nacimiento
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                        id="fecha_nacimiento"
                                        name="fecha_nacimiento"
                                        max="{{ date('Y-m-d') }}"
                                        value="{{ old('fecha_nacimiento') }}">

                                    @error('fecha_nacimiento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- TIPO DE USUARIO -->
                                <div class="col-md-6">
                                    <label for="rol_usuario_id" class="form-label">
                                        Tipo de usuario
                                    </label>

                                    <select
                                        class="form-select @error('rol_usuario_id') is-invalid @enderror"
                                        name="rol_usuario_id"
                                        id="rol_usuario_id"
                                        required>

                                        <option value="" disabled {{ old('rol_usuario_id') ? '' : 'selected' }}>
                                            Seleccionar tipo de usuario
                                        </option>

                                        @foreach ($rolesUsuario as $rol)
                                        <option
                                            value="{{ $rol->rol_usuario_id }}"
                                            {{ old('rol_usuario_id') == $rol->rol_usuario_id ? 'selected' : '' }}>
                                            {{ $rol->nombre }}
                                        </option>
                                        @endforeach
                                    </select>

                                    @error('rol_usuario_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- INSTITUCIÓN -->
                                <div class="col-md-6">
                                    <label for="institucion_id" class="form-label">
                                        Institución
                                    </label>

                                    <select
                                        class="form-select @error('institucion_id') is-invalid @enderror"
                                        name="institucion_id"
                                        id="institucion_id"
                                        required>

                                        <option value="" disabled {{ old('institucion_id') ? '' : 'selected' }}>
                                            Seleccionar institución
                                        </option>

                                        @foreach ($instituciones as $institucion)
                                        <option
                                            value="{{ $institucion->institucion_id }}"
                                            {{ old('institucion_id') == $institucion->institucion_id ? 'selected' : '' }}>
                                            {{ $institucion->nombre_institucion }}
                                        </option>
                                        @endforeach
                                    </select>

                                    @error('institucion_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                    Guardar usuario
                                </button>
                            </div>
                        </form>
                    </dialog>
                    <!-- CIERRA POP-UP DEL FORMULARIO DE REGISTRO -->
                    <div class="contenido-cuentas">
                        <p style="color: #2463A6">
                            {{ $totalUsuarios }} registrados
                        </p>

                        <p style="color: #198754">
                            {{ $usuariosActivos }} activos
                        </p>

                        <p style="color: #667085">
                            {{ $usuariosInactivos }} inactivos
                        </p>
                    </div>
                    <div class="panel">

                        @if (request()->hasAny(['buscar', 'rol', 'estado']))
                        <a href="{{ route('usuarios') }}" class="btn-limpiar">
                            <div class="pestanas">
                                <button type="button">Todos los usuarios</button>
                            </div>
                        </a>
                        @endif

                        <div class="busquedas">

                            {{-- Formulario funcional sin alterar el diseño --}}
                            <form action="{{ route('usuarios') }}"
                                method="GET"
                                id="formFiltros"
                                style="display: contents;">

                                {{-- Búsqueda --}}
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
                                        id="buscar"
                                        name="buscar"
                                        placeholder="Buscar por nombre, identificación o correo..."
                                        aria-label="Buscar usuarios"
                                        value="{{ request('buscar') }}">
                                </div>

                                {{-- Filtro por rol --}}
                                <div class="rol">
                                    <select
                                        id="rol"
                                        name="rol"
                                        class="combobox-rol"
                                        onchange="document.getElementById('formFiltros').requestSubmit()">

                                        <option value="">Rol: Todos</option>

                                        @foreach ($rolesUsuario as $rol)
                                        <option
                                            value="{{ $rol->rol_usuario_id }}"
                                            @selected(request('rol')==$rol->rol_usuario_id)>
                                            {{ $rol->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>


                                {{-- Filtro por institución --}}
                                <div class="institucion">
                                    <select
                                        id="institucion"
                                        name="institucion"
                                        class="combobox-institucion"
                                        onchange="document.getElementById('formFiltros').requestSubmit()">

                                        <option value="">Institución: Todas</option>

                                        @foreach ($instituciones as $institucion)
                                        <option
                                            value="{{ $institucion->institucion_id }}"
                                            @selected(request('institucion')==$institucion->institucion_id)>
                                            {{ $institucion->nombre_institucion }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>


                                {{-- Filtro por estado --}}
                                <div class="estados">
                                    <select
                                        id="estado"
                                        name="estado"
                                        class="combobox-estado"
                                        onchange="document.getElementById('formFiltros').requestSubmit()">

                                        <option value="">Estado: Todos</option>

                                        <option value="1" @selected(request('estado')==='1' )>
                                            Activo
                                        </option>

                                        <option value="0" @selected(request('estado')==='0' )>
                                            Inactivo
                                        </option>
                                    </select>
                                </div>

                            </form>

                        </div>
                        <!-- CIERRA BUSQUEDAS -->

                        <!-- ABRE TABLA -->
                        <div class="tabla">
                            <table class="tabla-users">
                                <thead>
                                    <tr>
                                        <th class="col-identificacion">Identificación</th>
                                        <th class="col-usuario">Nombre/Correo</th>
                                        <th class="col-telefono">Teléfono</th>
                                        <th class="col-rol">Rol</th>
                                        <th class="col-tipo">Institucion</th>
                                        <th class="col-estado">Estado</th>
                                        <th class="col-suscripcion">Suscripcion</th>
                                        <th class="col-acciones">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($usuarios as $usuario)
                                    <tr>
                                        <td class="col-identificacion">
                                            <div class="dato-principal">
                                                {{ $usuario->documento }}
                                            </div>
                                        </td>

                                        <td class="col-usuario">
                                            <div class="dato-principal">
                                                {{ $usuario->nombre }} {{ $usuario->apellido }}
                                            </div>

                                            <div class="dato-secundario">
                                                {{ $usuario->correo ?? 'Sin correo registrado' }}
                                            </div>
                                        </td>

                                        <td class="col-telefono">
                                            <div class="dato-principal">
                                                {{ $usuario->telefono ?? 'Sin teléfono registrado' }}
                                            </div>
                                        </td>

                                        <td class="col-rol">
                                            <div class="dato-principal">
                                                {{ $usuario->nombre_rol ?? 'Sin rol' }}
                                            </div>
                                        </td>

                                        <td class="col-tipo">
                                            <div class="dato-principal">
                                                {{ $usuario->nombre_institucion ?? 'Sin institución' }}
                                            </div>
                                        </td>

                                        <td class="col-estado">
                                            @if ($usuario->estado_usuario)
                                            <span class="estado activo">Activo</span>
                                            @else
                                            <span class="estado inactivo">Inactivo</span>
                                            @endif
                                        </td>

                                        <td class="col-suscripcion">
                                            <div class="dato-principal">
                                                No aplica
                                            </div>
                                        </td>

                                        <td class="col-acciones">
                                            <div class="dato-principal">
                                                <a href="#">Ver</a>
                                                <a href="#">Editar</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="sin-registros">
                                            No hay usuarios registrados.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- CIERRA TABLA -->

                        <!-- ABRE PAGINACION -->
                        <div class="paginacion">

                            <p class="texto-registros">
                                Mostrando {{ $usuarios->firstItem() ?? 0 }}-{{ $usuarios->lastItem() ?? 0 }}
                                de {{ $usuarios->total() }} registros
                            </p>

                            <nav aria-label="Navegación de páginas">
                                <ul class="pagination">

                                    {{-- Botón anterior --}}
                                    @if ($usuarios->onFirstPage())
                                    <li>
                                        <span class="page-link pagina-anterior disabled">
                                            &laquo; Anterior
                                        </span>
                                    </li>
                                    @else
                                    <li>
                                        <a href="{{ $usuarios->previousPageUrl() }}"
                                            class="page-link pagina-anterior">
                                            &laquo; Anterior
                                        </a>
                                    </li>
                                    @endif

                                    {{-- Números de página --}}
                                    @foreach ($usuarios->getUrlRange(1, $usuarios->lastPage()) as $pagina => $url)
                                    <li>
                                        @if ($pagina == $usuarios->currentPage())
                                        <span class="page-link active" aria-current="page">
                                            {{ $pagina }}
                                        </span>
                                        @else
                                        <a href="{{ $url }}" class="page-link">
                                            {{ $pagina }}
                                        </a>
                                        @endif
                                    </li>
                                    @endforeach

                                    {{-- Botón siguiente --}}
                                    @if ($usuarios->hasMorePages())
                                    <li>
                                        <a href="{{ $usuarios->nextPageUrl() }}"
                                            class="page-link pagina-siguiente">
                                            Siguiente &raquo;
                                        </a>
                                    </li>
                                    @else
                                    <li>
                                        <span class="page-link pagina-siguiente disabled">
                                            Siguiente &raquo;
                                        </span>
                                    </li>
                                    @endif

                                </ul>
                            </nav>

                        </div>
                        <!-- CIERRA PAGINACION -->
                    </div>
                </div>
            </main>
            <!-- CIERRA CONTENIDO DE USUARIOS -->
        </div>
        <!-- CIERRA PANEL DERECHO -->
    </div>
    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalRegistro');

            if (modal && !modal.open) {
                modal.showModal();
            }
        });
    </script>
    @endif
</body>

</html>