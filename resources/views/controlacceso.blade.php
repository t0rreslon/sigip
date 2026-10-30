<!DOCTYPE html>
<html lang="en">
<head>
    <!-- meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- estilos de usuarios y estilos del panel izquierdo -->
    @vite(['resources/js/app.js'])
    <!-- javascrpit del pop-out -->
    
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
    <!-- fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <!-- titulo -->
    <title>Control de Accesos</title>
</head>
<body>
    <!-- ABRE FLEXBOX -->
    <div class="flexbox">
        <!-- ABRE PANEL IZQUIERDO -->
        <x-panel-izq />
        <!-- CIERRA PANEL IZQUIERDO -->
        <!-- ----------------------------------------------------------------------------------- -->
        <!-- ABRE PANEL DERECHO -->
        <div class="panel-dere">
            <!-- ABRE NAVBAR -->
            <x-nav_bar />
            <!-- CIERRA NAVBAR -->
            <!-- ABRE CONTENIDO PRINCIPAL -->
            <main class="contenido-principal">
                <!-- ABRE CONTENIDO DE CONTROL DE ACCESO -->
                <div class="contenido-control-acceso">
                    <!-- ABRE ENCABEZADO DE LA SECCION -->
                    <div class="encabezado-seccion">
                        <h1>Control de acceso</h1>
                        <p>Valida las condiciones del usuario antes de registrar el ingreso.</p>
                    </div>
                    <!-- CIERRA ENCABEZADO -->
                    <!-- ABRE CONSULTA DE IDENTIFICACION -->
                    <div class="consulta-identificacion">
                        <!-- ABRE BARRA DE BUSQUEDA -->
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
                        <!-- CIERRA BARRA DE BUSQUEDA -->
                        <!-- ABRE BOTON DE CONSULTAR IDENTIFICACION -->
                        <button type="button" id="consultar-identificacion">
                        <img width="24" height="24" src="https://img.icons8.com/fluency-systems-regular/24/search--v1.png" alt="search--v1" />
                            Consultar Identificación
                        </button>
                        <!-- ABRE BOTON DE LECTURA QR -->
                        <button type="button" id="lectura-qr">
                        <img src="https://img.icons8.com/external-anggara-glyph-anggara-putra/32/external-scan-user-interface-anggara-basic-outline-anggara-putra-2.png"
                        alt="">
                        Lectura QR opcional
                        </button>
                    </div>
                    <!-- CIERRA CONSULTA DE IDENTIFICACION -->
                    <!-- ABRE VALIDACION DE INGRESO -->
                    <div class="validacion-ingreso">
                        
                        <!-- TARJETA DE DATOS DEL USUARIO (IZQUIERDA) -->
                        <div class="tarjeta-usuario">
                            <div class="usuario-header">
                                <h2>Mariana López</h2>
                                <p class="usuario-id">1032456789 · Estudiante</p>
                            </div>
                            <div class="usuario-grid">
                                <div class="campo">
                                    <span class="label">Estado del usuario</span>
                                    <span class="valor">Activo</span>
                                </div>
                                <div class="campo">
                                    <span class="label">Plan</span>
                                    <span class="valor">Mensual universitario</span>
                                </div>
                                <div class="campo">
                                    <span class="label">Vigencia de suscripción</span>
                                    <span class="valor">02 oct—31 oct 2026</span>
                                </div>
                                <div class="campo">
                                    <span class="label">Pago</span>
                                    <span class="valor">$ 35.000 · Verificado</span>
                                </div>
                                <div class="campo">
                                    <span class="label">Correo</span>
                                    <span class="valor">mariana.lopez@correo.demo</span>
                                </div>
                                <div class="campo">
                                    <span class="label">Teléfono</span>
                                    <span class="valor">300 456 7890</span>
                                </div>
                            </div>
                            <div class="usuario-footer">
                                <p>Referencia verificada: DEMO-0210-12 · Carlos Ruiz</p>
                            </div>
                        </div>
                        <!-- TARJETA DE ESTADO DE ACCESO (DERECHA) -->
                        <div class="tarjeta-estado-acceso">
                            <div class="icono-estado">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#137333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                            </div>
                            <h3 class="titulo-estado">Acceso autorizado</h3>
                            <p class="descripcion-estado">Usuario activo, suscripción vigente y pago verificado.</p>
                            <div class="info-fecha">
                                <span class="label">Fecha y hora de consulta</span>
                                <span class="valor">22 oct 2026 · 10:30</span>
                            </div>
                            <button type="button" class="btn-registrar-ingreso">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                    <polyline points="10 17 15 12 10 7"></polyline>
                                    <line x1="15" y1="12" x2="3" y2="12"></line>
                                </svg>
                                Registrar ingreso
                            </button>
                            <p class="nota-advertencia">
                                Solo registrar cuando la persona ingrese. La consulta por sí sola no crea un acceso.
                            </p>
                        </div>
                    </div>
                    <!-- CIERRA VALIDACION DE INGRESO -->
                    <!-- ABRE HISTORIAL RECIENTE DE ACCESOS -->
                    <div class="historial-accesos">
                        <!-- ENCABEZADO DEL PANEL -->
                        <div class="historial-header">
                            <h2>Historial reciente de accesos</h2>
                            <p>Jornada de hoy · 64 ingresos autorizados</p>
                        </div>

                        <!-- TABLA DE REGISTROS -->
                        <div class="historial-tabla-wrapper">
                            <table class="historial-tabla">
                                <thead>
                                    <tr>
                                        <th class="col-hora">Hora</th>
                                        <th class="col-id">Identificación</th>
                                        <th class="col-usuario">Usuario</th>
                                        <th class="col-resultado">Resultado / razón</th>
                                        <th class="col-operador">Operador</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>10:24</td>
                                        <td>1032456789</td>
                                        <td class="nombre-usuario">Mariana López</td>
                                        <td>Autorizado · Suscripción vigente</td>
                                        <td>Carlos Ruiz</td>
                                    </tr>
                                    <tr>
                                        <td>10:18</td>
                                        <td>1034567890</td>
                                        <td class="nombre-usuario">Sara Restrepo</td>
                                        <td>Autorizado · Suscripción vigente</td>
                                        <td>Carlos Ruiz</td>
                                    </tr>
                                    <tr>
                                        <td>10:12</td>
                                        <td>1023456780</td>
                                        <td class="nombre-usuario">Mateo Torres</td>
                                        <td>Denegado · Suscripción vencida</td>
                                        <td>Carlos Ruiz</td>
                                    </tr>
                                    <tr>
                                        <td>09:55</td>
                                        <td>1045678901</td>
                                        <td class="nombre-usuario">Diego Ramírez</td>
                                        <td>Requiere verificación · Pago pendiente</td>
                                        <td>Carlos Ruiz</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- CIERRA HISTORIAL RECIENTE DE ACCESOS -->
                </div>
                <!-- CIERRA CONTENIDO DE CONTROL DE ACCESO -->
            </main>
            <!-- CIERRA CONTENIDO PRINCIPAL -->
        </div>
        <!-- CIERRA PANEL DERECHO -->
    </div>
    <!-- CIERRA FLEXBOX -->
</body>
</html>