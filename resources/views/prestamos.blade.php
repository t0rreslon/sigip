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
                    <!-- ABRE ENCABEZADO SECCION -->
                    <div class="encabezado-seccion">
                        <h1>Prestamos y devoluciones</h1>
                        <p>Controla unidades disponibles, responsables y devoluciones.</p>
                        <button type="button" id="abrir-dialogo">
                            <img width="24" height="24" src="https://img.icons8.com/android/24/plus.png" alt="plus"/>
                            Registrar prestamo
                        </button>
                    </div>
                    <!-- CIERRA ENCABEZADO SECCION -->
                    <!-- ABRE VENTANA MODAL PARA REGISTRAR PRESTAMO -->
                    <!-- MODAL DE REGISTRO DE USUARIOS -->
                    <dialog id="modalRegistro" class="modal-registro">
                        <div class="modal-encabezado">
                            <div>
                                <h2>Registrar prestamo</h2>
                                <p>Ingresa la información para registrar un prestamo en SIGIP.</p>
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
                                <!-- USUARIO -->
                                <div class="col-md-6">
                                    <label for="documento" class="form-label">Documento</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="documento"
                                        name="documento"
                                        placeholder="Ingrese el documento del usuario"
                                        autocomplete="given-name"
                                        required
                                    >
                                </div>
                                <!-- DOCUMENTO OPERARIO -->
                                <div class="col-md-6">
                                    <label for="documento_op" class="form-label">Documento del operador</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="documento_op"
                                        name="documento_op"
                                        placeholder="Ingrese documento del operario"
                                        autocomplete="given-name"
                                        required
                                    >
                                </div>
                                <!-- FECHA DE PRESTAMO -->
                                <div class="col-md-6">
                                    <label for="fecha_prestamo" class="form-label">
                                        Fecha de prestamo
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="fecha_prestamo"
                                        name="fecha_prestamo"
                                        required
                                    >
                                </div>
                                <!-- FECHA DE DEVOLUCION -->
                                <div class="col-md-6">
                                    <label for="fecha_devolucion" class="form-label">
                                        Fecha de devolucion
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="fecha_devolucion"
                                        name="fecha_devolucion"
                                        required
                                    >
                                </div>
                                <!-- ESTADO -->
                                <div class="col-md-6">
                                    <label for="estado_prestamo" class="form-label">
                                        Estado del usuario
                                    </label>
                                    <select
                                        class="form-select"
                                        id="estado_prestamo"
                                        name="estado_prestamo"
                                        required
                                    >
                                        <option value="" selected disabled>
                                            Seleccionar estado
                                        </option>
                                        <option value="prendiente">Pendiente</option>
                                        <option value="finalizado">Finalizado</option>
                                        <option value="en-curso">En curso</option>
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
                    <!-- ABRE DISPONIBILIDAD DE ELEMENTOS -->
                    <div class="disponibilidad-elementos">
                        <!-- TARJETA 1: Colchonetas -->
                        <div class="tarjeta-metrica">
                            <div class="tarjeta-metrica-header">
                                <span class="titulo-implemento">Colchonetas · IMP-01</span>
                                <div class="icono-metrica">
                                    <!-- Icono Mancuerna / Equipamiento -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2463A6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6.5 6.5 11 11"></path>
                                        <path d="m21 21-1 1a2 2 0 0 1-2.83 0l-2.5-2.5a2 2 0 0 1 0-2.83l.33-.34 4.5 4.5.5-.33Z"></path>
                                        <path d="m3 3 1-1a2 2 0 0 1 2.83 0l2.5 2.5a2 2 0 0 1 0 2.83l-.33.34-4.5-4.5L4 3.5Z"></path>
                                        <path d="m14.5 9.5 5-5"></path>
                                        <path d="m4.5 19.5 5-5"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="tarjeta-metrica-cuerpo">
                                <span class="cantidad">8 / 12</span>
                                <span class="detalle">2 prestadas · 2 en mantenimiento</span>
                            </div>
                        </div>
                        <!-- TARJETA 2: Bandas -->
                        <div class="tarjeta-metrica">
                            <div class="tarjeta-metrica-header">
                                <span class="titulo-implemento">Bandas · IMP-02</span>
                                <div class="icono-metrica">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2463A6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6.5 6.5 11 11"></path>
                                        <path d="m21 21-1 1a2 2 0 0 1-2.83 0l-2.5-2.5a2 2 0 0 1 0-2.83l.33-.34 4.5 4.5.5-.33Z"></path>
                                        <path d="m3 3 1-1a2 2 0 0 1 2.83 0l2.5 2.5a2 2 0 0 1 0 2.83l-.33.34-4.5-4.5L4 3.5Z"></path>
                                        <path d="m14.5 9.5 5-5"></path>
                                        <path d="m4.5 19.5 5-5"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="tarjeta-metrica-cuerpo">
                                <span class="cantidad">16 / 20</span>
                                <span class="detalle">4 prestadas · Sin novedades</span>
                            </div>
                        </div>
                        <!-- TARJETA 3: Balones -->
                        <div class="tarjeta-metrica">
                            <div class="tarjeta-metrica-header">
                                <span class="titulo-implemento">Balones · IMP-03</span>
                                <div class="icono-metrica">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2463A6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6.5 6.5 11 11"></path>
                                        <path d="m21 21-1 1a2 2 0 0 1-2.83 0l-2.5-2.5a2 2 0 0 1 0-2.83l.33-.34 4.5 4.5.5-.33Z"></path>
                                        <path d="m3 3 1-1a2 2 0 0 1 2.83 0l2.5 2.5a2 2 0 0 1 0 2.83l-.33.34-4.5-4.5L4 3.5Z"></path>
                                        <path d="m14.5 9.5 5-5"></path>
                                        <path d="m4.5 19.5 5-5"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="tarjeta-metrica-cuerpo">
                                <span class="cantidad">6 / 6</span>
                                <span class="detalle">Todos disponibles</span>
                            </div>
                        </div>
                        <!-- TARJETA 4: Préstamos activos -->
                        <div class="tarjeta-metrica">
                            <div class="tarjeta-metrica-header">
                                <span class="titulo-implemento">Préstamos activos</span>
                                <div class="icono-metrica">
                                    <!-- Icono Apreton de manos / Préstamo -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2463A6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m11 17 2 2a1 1 0 0 0 1.4 0l3.6-3.6a1 1 0 0 0 0-1.4l-2-2"></path>
                                        <path d="m14 14 2.5 2.5a1 1 0 0 0 1.4 0l2.6-2.6a1 1 0 0 0 0-1.4l-3-3"></path>
                                        <path d="M10 11 7 8a1 1 0 0 0-1.4 0l-2.6 2.6a1 1 0 0 0 0 1.4l3 3"></path>
                                        <path d="m7 14-2 2a1 1 0 0 0 0 1.4l3.6 3.6a1 1 0 0 0 1.4 0l2-2"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="tarjeta-metrica-cuerpo">
                                <span class="cantidad">4</span>
                                <span class="detalle">2 con devolución hoy · 2 mañana</span>
                            </div>
                        </div>
                    </div>
                    <!-- CIERRA DISPONIBILIDAD DE ELEMENTOS -->
                    <!-- ABRE PANEL DE PRESTAMOS -->
                    <div class="panel">
                    <div class="pestanas">
                        <button type="button">Préstamos activos</button>
                        <button type="button">Historial de devoluciones</button>
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
                                placeholder="Buscar implemento, código o responsable"
                                aria-label="Buscar implemento">
                        </div>
                        <div class="categoria">
                            <select id="rol" name="rol" class="combobox-categoria" required>
                                <option value="" disabled selected>Categoria: Seleccionar categoria</option>
                                <option value="bandas">Bandas</option>
                                <option value="balones">Balones</option>
                                <option value="pesas">Pesas</option>
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
                        <table class="tabla-prestamos">
                            <thead>
                                <tr>
                                    <th class="col-identificacion">Prestamos/implemento</th>
                                    <th class="col-categoria">Categoria / cant.</th>
                                    <th class="col-responsable">Responsable</th>
                                    <th class="col-prestamo">Prestamo</th>
                                    <th class="col-devolucion">Devolucion prevista</th>
                                    <th class="col-estado">Estado / acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">PR-041 · IMP-01</div>
                                        <div class="dato-secundario">Colchoneta</div>
                                    </td>
                                    <td class="col-categoria">
                                        <div class="dato-principal">Entrenamiento · 2</div>
                                    </td>
                                    <td class="col-responsable">
                                        <div class="dato-principal">Mariana López</div>
                                    </td>
                                    <td class="col-prestamo">
                                        <div class="dato-principal">22 oct · 10:15</div>
                                    </td>
                                    <td class="col-devolucion">
                                        <div class="dato-principal">22 oct · 11:15</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                        <button class="registrar-devolucion">
                                            Registrar devolucion
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">PR-041 · IMP-01</div>
                                        <div class="dato-secundario">Colchoneta</div>
                                    </td>
                                    <td class="col-categoria">
                                        <div class="dato-principal">Entrenamiento · 2</div>
                                    </td>
                                    <td class="col-responsable">
                                        <div class="dato-principal">Mariana López</div>
                                    </td>
                                    <td class="col-prestamo">
                                        <div class="dato-principal">22 oct · 10:15</div>
                                    </td>
                                    <td class="col-devolucion">
                                        <div class="dato-principal">22 oct · 11:15</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                        <button class="registrar-devolucion">
                                            Registrar devolucion
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">PR-041 · IMP-01</div>
                                        <div class="dato-secundario">Colchoneta</div>
                                    </td>
                                    <td class="col-categoria">
                                        <div class="dato-principal">Entrenamiento · 2</div>
                                    </td>
                                    <td class="col-responsable">
                                        <div class="dato-principal">Mariana López</div>
                                    </td>
                                    <td class="col-prestamo">
                                        <div class="dato-principal">22 oct · 10:15</div>
                                    </td>
                                    <td class="col-devolucion">
                                        <div class="dato-principal">22 oct · 11:15</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                        <button class="registrar-devolucion">
                                            Registrar devolucion
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-identificacion">
                                        <div class="dato-principal">PR-041 · IMP-01</div>
                                        <div class="dato-secundario">Colchoneta</div>
                                    </td>
                                    <td class="col-categoria">
                                        <div class="dato-principal">Entrenamiento · 2</div>
                                    </td>
                                    <td class="col-responsable">
                                        <div class="dato-principal">Mariana López</div>
                                    </td>
                                    <td class="col-prestamo">
                                        <div class="dato-principal">22 oct · 10:15</div>
                                    </td>
                                    <td class="col-devolucion">
                                        <div class="dato-principal">22 oct · 11:15</div>
                                    </td>
                                    <td class="col-estado">
                                        <span class="estado activo">Activo</span>
                                        <button class="registrar-devolucion" id="abrir-dialogo">
                                            Registrar devolucion
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <dialog id="modalRegistro" class="modal-registro">
                        <div class="modal-encabezado">
                            <div>
                                <h2>Registrar prestamo</h2>
                                <p>Ingresa la información para registrar un prestamo en SIGIP.</p>
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
                                <!-- USUARIO -->
                                <div class="col-md-6">
                                    <label for="documento" class="form-label">Documento</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="documento"
                                        name="documento"
                                        placeholder="Ingrese el documento del usuario"
                                        autocomplete="given-name"
                                        required
                                    >
                                </div>
                                <!-- DOCUMENTO OPERARIO -->
                                <div class="col-md-6">
                                    <label for="documento_op" class="form-label">Documento del operador</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="documento_op"
                                        name="documento_op"
                                        placeholder="Ingrese documento del operario"
                                        autocomplete="given-name"
                                        required
                                    >
                                </div>
                                <!-- FECHA DE PRESTAMO -->
                                <div class="col-md-6">
                                    <label for="fecha_prestamo" class="form-label">
                                        Fecha de prestamo
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="fecha_prestamo"
                                        name="fecha_prestamo"
                                        required
                                    >
                                </div>
                                <!-- FECHA DE DEVOLUCION -->
                                <div class="col-md-6">
                                    <label for="fecha_devolucion" class="form-label">
                                        Fecha de devolucion
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="fecha_devolucion"
                                        name="fecha_devolucion"
                                        required
                                    >
                                </div>
                                <!-- ESTADO -->
                                <div class="col-md-6">
                                    <label for="estado_prestamo" class="form-label">
                                        Estado del usuario
                                    </label>
                                    <select
                                        class="form-select"
                                        id="estado_prestamo"
                                        name="estado_prestamo"
                                        required
                                    >
                                        <option value="" selected disabled>
                                            Seleccionar estado
                                        </option>
                                        <option value="prendiente">Pendiente</option>
                                        <option value="finalizado">Finalizado</option>
                                        <option value="en-curso">En curso</option>
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
                    <!-- CIERRA PANEL DE PRESTAMOS -->
                </div>
                <!-- CIERRA CONTENIDO PRESTAMOS -->
            </main>
            <!-- CIERRA CONTENIDO PRINICPAL -->
        </div>
        <!-- ABRE PANEL IZQUIERDO -->
    </div>
    <!-- CIERRA FLEXBOX -->
</body>
</html>