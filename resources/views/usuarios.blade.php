<!DOCTYPE html>
<html lang="en">
<head>
    <!-- meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- estilos de usuarios y estilos del panel izquierdo -->
    @vite(['resources/js/app.js'])
    <!-- javascrpit del pop-out -->
    @vite(['resources/js/app.js', 'resources/js/pop_out.js'])
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
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
                <div class="encabezado-seccion">
                    <h1>Usuarios</h1>
                    <p>Consulta y administra cuentas, roles y condiciones de la comunidad.</p>
                    <button type="button" id="abrir-dialogo">
                        <img width="24" height="24" src="https://img.icons8.com/external-thin-kawalan-studio/24/external-user-plus-users-thin-kawalan-studio.png" alt="external-user-plus-users-thin-kawalan-studio"/>
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
                            aria-label="Cerrar formulario"
                        >
                            &times;
                        </button>
                    </div>
                    <form id="formRegistro">
                        <div class="row g-3">
                            <!-- NOMBRE -->
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="nombre"
                                    name="nombre"
                                    placeholder="Ingrese el nombre"
                                    autocomplete="given-name"
                                    required
                                >
                            </div>
                            <!-- APELLIDO -->
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="apellido"
                                    name="apellido"
                                    placeholder="Ingrese el apellido"
                                    autocomplete="family-name"
                                    required
                                >
                            </div>
                            <!-- TIPO DE DOCUMENTO -->
                            <div class="col-md-6">
                                <label for="tipo_documento_id" class="form-label">
                                    Tipo de documento
                                </label>
                                <select
                                    class="form-select"
                                    id="tipo_documento_id"
                                    name="tipo_documento_id"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Seleccionar documento
                                    </option>
                                    <option value="1">Cédula de ciudadanía</option>
                                    <option value="2">Tarjeta de identidad</option>
                                    <option value="3">Cédula de extranjería</option>
                                    <option value="4">Pasaporte</option>
                                </select>
                            </div>
                            <!-- DOCUMENTO -->
                            <div class="col-md-6">
                                <label for="documento" class="form-label">
                                    Número de documento
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="documento"
                                    name="documento"
                                    placeholder="Ingrese el documento"
                                    autocomplete="off"
                                    required
                                >
                            </div>
                            <!-- GÉNERO -->
                            <div class="col-md-6">
                                <label for="genero_id" class="form-label">Género</label>
                                <select
                                    class="form-select"
                                    id="genero_id"
                                    name="genero_id"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Seleccionar género
                                    </option>
                                    <option value="1">Femenino</option>
                                    <option value="2">Masculino</option>
                                    <option value="3">Otro</option>
                                    <option value="4">Prefiero no indicarlo</option>
                                </select>
                            </div>
                            <!-- EPS -->
                            <div class="col-md-6">
                                <label for="eps_id" class="form-label">EPS</label>
                                <select
                                    class="form-select"
                                    id="eps_id"
                                    name="eps_id"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Seleccionar EPS
                                    </option>
                                    <option value="1">SURA</option>
                                    <option value="2">Nueva EPS</option>
                                    <option value="3">Sanitas</option>
                                    <option value="4">Otra</option>
                                </select>
                            </div>
                            <!-- CORREO -->
                            <div class="col-md-6">
                                <label for="correo" class="form-label">
                                    Correo electrónico
                                </label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="correo"
                                    name="correo"
                                    placeholder="usuario@correo.com"
                                    autocomplete="email"
                                    required
                                >
                            </div>
                            <!-- TELÉFONO -->
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input
                                    type="tel"
                                    class="form-control"
                                    id="telefono"
                                    name="telefono"
                                    placeholder="Ingrese el teléfono"
                                    autocomplete="tel"
                                    required
                                >
                            </div>
                            <!-- FECHA DE NACIMIENTO -->
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">
                                    Fecha de nacimiento
                                </label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="fecha_nacimiento"
                                    name="fecha_nacimiento"
                                    required
                                >
                            </div>
                            <!-- ROL -->
                            <div class="col-md-6">
                                <label for="rol_usuario_id" class="form-label">
                                    Rol del usuario
                                </label>
                                <select
                                    class="form-select"
                                    id="rol_usuario_id"
                                    name="rol_usuario_id"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Seleccionar rol
                                    </option>
                                    <option value="1">Administrador</option>
                                    <option value="2">Operador</option>
                                    <option value="3">Instructor</option>
                                    <option value="4">Usuario del gimnasio</option>
                                </select>
                            </div>
                            <!-- INSTITUCIÓN -->
                            <div class="col-md-6">
                                <label for="institucion_id" class="form-label">
                                    Institución
                                </label>
                                <select
                                    class="form-select"
                                    id="institucion_id"
                                    name="institucion_id"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Seleccionar institución
                                    </option>
                                    <option value="1">
                                        Institución Universitaria Pascual Bravo
                                    </option>
                                    <option value="2">Otra institución</option>
                                </select>
                            </div>
                            <!-- ESTADO -->
                            <div class="col-md-6">
                                <label for="estado_usuario" class="form-label">
                                    Estado del usuario
                                </label>
                                <select
                                    class="form-select"
                                    id="estado_usuario"
                                    name="estado_usuario"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Seleccionar estado
                                    </option>
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <!-- BOTONES -->
                        <div class="modal-acciones">
                            <button
                                type="button"
                                id="btnCancelar"
                                class="btn-cancelar"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="btn-guardar"
                            >
                                Guardar usuario
                            </button>
                        </div>
                    </form>
                </dialog>
            <!-- CIERRA POP-UP DEL FORMULARIO DE REGISTRO -->
                <div class="contenido-cuentas">
                    <!-- se deberian de cambiar los nombres de los id, ya que no son nada descriptivos ni intituivos -->
                    <p style="color: #2463A6" id="blue">128 registrados</p>
                    <p style="color: #198754" id="green">112 activos</p>
                    <p style="color: #667085" id="gray">16 inactivos</p>
                </div>
                <div class="panel">
                    <div class="pestanas">
                        <button type="button">Todos los usuarios</button>
                        <button type="button">Personal del gimansio</button>
                    </div>
                    <div class="busquedas">
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
                                aria-hidden="true"
                                >
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>
                            <!-- Campo de búsqueda -->
                            <input
                                type="search"
                                class="barra-busqueda"
                                placeholder="Buscar por nombre, identificación o correo..."
                                aria-label="Buscar usuarios">
                        </div>
                        <div class="rol">
                            <select id="rol" name="rol" class="combobox-rol" required>
                                <option value="" disabled selected>Rol: Seleccionar rol</option>
                                <option value="administrador">Administrador</option>
                                <option value="operador">Operador</option>
                                <option value="instructor">Instructor</option>
                            </select>
                        </div>
                        <div class="estados">
                            <select id="estado" name="estado" class="combobox-estado" required>
                                <option value="" disabled selected>Estado: Seleccionar estado</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        
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
                                    <th class="col-tipo">Tipo</th>
                                    <th class="col-estado">Estado</th>
                                    <th class="col-suscripcion">Suscripcion</th>
                                    <th class="col-acciones">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">1002456789</div>
                                    </td>
                                    <td class="col-usuario">
                                        <div class="dato-principal">María Fernanda López</div>
                                        <div class="dato-secundario">maria.lopez@pascualbravo.edu.co</div>
                                    </td>
                                    <td class="col-telefono">
                                        <div class="dato-principal">3186067977</div>
                                    </td>
                                    <td class="col-rol">
                                        <div class="dato-principal">Administrador</div>
                                    </td>
                                    <td class="col-tipo">
                                        <div class="dato-principal">Empleado</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                    </td>
                                    <td class="col-suscripcion">
                                        <div class="dato-principal">No aplica</div>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="dato-principal">
                                            <a href="#">o</a>
                                            <a href="#">I</a>
                                            <a href="#">/</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">1002456789</div>
                                    </td>
                                    <td class="col-usuario">
                                        <div class="dato-principal">María Fernanda López</div>
                                        <div class="dato-secundario">maria.lopez@pascualbravo.edu.co</div>
                                    </td>
                                    <td class="col-telefono">
                                        <div class="dato-principal">3186067977</div>
                                    </td>
                                    <td class="col-rol">
                                        <div class="dato-principal">Administrador</div>
                                    </td>
                                    <td class="col-tipo">
                                        <div class="dato-principal">Empleado</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                    </td>
                                    <td class="col-suscripcion">
                                        <div class="dato-principal">No aplica</div>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="dato-principal">
                                            <a href="#">o</a>
                                            <a href="#">I</a>
                                            <a href="#">/</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">1002456789</div>
                                    </td>
                                    <td class="col-usuario">
                                        <div class="dato-principal">María Fernanda López</div>
                                        <div class="dato-secundario">maria.lopez@pascualbravo.edu.co</div>
                                    </td>
                                    <td class="col-telefono">
                                        <div class="dato-principal">3186067977</div>
                                    </td>
                                    <td class="col-rol">
                                        <div class="dato-principal">Administrador</div>
                                    </td>
                                    <td class="col-tipo">
                                        <div class="dato-principal">Empleado</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                    </td>
                                    <td class="col-suscripcion">
                                        <div class="dato-principal">No aplica</div>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="dato-principal">
                                            <a href="#">o</a>
                                            <a href="#">I</a>
                                            <a href="#">/</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">1002456789</div>
                                    </td>
                                    <td class="col-usuario">
                                        <div class="dato-principal">María Fernanda López</div>
                                        <div class="dato-secundario">maria.lopez@pascualbravo.edu.co</div>
                                    </td>
                                    <td class="col-telefono">
                                        <div class="dato-principal">3186067977</div>
                                    </td>
                                    <td class="col-rol">
                                        <div class="dato-principal">Administrador</div>
                                    </td>
                                    <td class="col-tipo">
                                        <div class="dato-principal">Empleado</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                    </td>
                                    <td class="col-suscripcion">
                                        <div class="dato-principal">No aplica</div>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="dato-principal">
                                            <a href="#">o</a>
                                            <a href="#">I</a>
                                            <a href="#">/</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">1002456789</div>
                                    </td>
                                    <td class="col-usuario">
                                        <div class="dato-principal">María Fernanda López</div>
                                        <div class="dato-secundario">maria.lopez@pascualbravo.edu.co</div>
                                    </td>
                                    <td class="col-telefono">
                                        <div class="dato-principal">3186067977</div>
                                    </td>
                                    <td class="col-rol">
                                        <div class="dato-principal">Administrador</div>
                                    </td>
                                    <td class="col-tipo">
                                        <div class="dato-principal">Empleado</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                    </td>
                                    <td class="col-suscripcion">
                                        <div class="dato-principal">No aplica</div>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="dato-principal">
                                            <a href="#">o</a>
                                            <a href="#">I</a>
                                            <a href="#">/</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">1002456789</div>
                                    </td>
                                    <td class="col-usuario">
                                        <div class="dato-principal">María Fernanda López</div>
                                        <div class="dato-secundario">maria.lopez@pascualbravo.edu.co</div>
                                    </td>
                                    <td class="col-telefono">
                                        <div class="dato-principal">3186067977</div>
                                    </td>
                                    <td class="col-rol">
                                        <div class="dato-principal">Administrador</div>
                                    </td>
                                    <td class="col-tipo">
                                        <div class="dato-principal">Empleado</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado inactivo">Inactivo</span>
                                    </td>
                                    <td class="col-suscripcion">
                                        <div class="dato-principal">No aplica</div>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="dato-principal">
                                            <a href="#">o</a>
                                            <a href="#">I</a>
                                            <a href="#">/</a>
                                        </div>
                                    </td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                    <!-- CIERRA TABLA -->
                    <!-- ABRE PAGINACION -->
                    <div class="paginacion">
                        <p class="texto-registros">Mostrando 1-3 de 128 registros</p>
                        <nav aria-label="Navegación de páginas">
                            <ul class="pagination">
                                <li>
                                    <a href="#" class="page-link pagina-anterior">
                                        &laquo; Anterior
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="page-link active" aria-current="page">1</a>
                                </li>
                                <li>
                                    <a href="#" class="page-link">2</a>
                                </li>
                                <li>
                                    <a href="#" class="page-link">3</a>
                                </li>
                                <li>
                                    <a href="#" class="page-link">4</a>
                                </li>
                                <li>
                                    <a href="#" class="page-link pagina-siguiente">
                                        Siguiente &raquo;
                                    </a>
                                </li>
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
    </body>
</html>