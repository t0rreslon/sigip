
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGIP | Pagos</title>

    @vite([
        'resources/css/styles.css',
        'resources/js/app.js',
        'resources/css/panel_izq.css',
        'resources/css/nav_bar.css'
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <script src="components/panel_izq.js"></script>

    <div class="flexbox">
        <x-panel-izq />

        <div class="contenido">
            <x-nav_bar />

            <main class="pagina">

                <div class="pagina-titulo">
                    <div>
                        <h1>Pagos</h1>
                        <p>Registro, consulta y revisión de pagos de suscripciones.</p>
                    </div>
                </div>

                {{-- MENSAJES --}}
                @if (session('mensaje'))
                    <div class="mensaje-pago" id="mensaje">
                        <span>✓</span>
                        {{ session('mensaje') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mensaje-error-pago" id="mensaje-error">
                        <strong>No se pudo completar la operación:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- CONTADORES --}}
                <div class="resumen-pagos">
                    <div class="resumen-pago">
                        <span>Pendientes</span>
                        <strong>{{ $pendientes }}</strong>
                    </div>

                    <div class="resumen-pago">
                        <span>Verificados</span>
                        <strong>{{ $verificados }}</strong>
                    </div>

                    <div class="resumen-pago">
                        <span>Rechazados</span>
                        <strong>{{ $rechazados }}</strong>
                    </div>
                </div>

                <div class="pagos-grid">

                    {{-- ================================= --}}
                    {{-- BANDEJA DE PAGOS --}}
                    {{-- ================================= --}}

                    <section class="tarjeta bandeja-pagos">

                        <div class="encabezado-tarjeta-pagos">
                            <div>
                                <h2>Bandeja de pagos</h2>
                                <small class="tarjeta-sub">
                                    Consulta y administra los pagos registrados.
                                </small>
                            </div>
                        </div>

                        {{-- BÚSQUEDA Y FILTROS --}}
                        <form action="{{ route('pago.index') }}" method="GET" class="filtros-pagos">

                            <div class="filtro-busqueda">
                                <label for="buscar-pago">Buscar</label>
                                <input
                                    type="search"
                                    id="buscar-pago"
                                    name="buscar"
                                    value="{{ request('buscar') }}"
                                    placeholder="Nombre, apellido, documento o plan">
                            </div>

                            <div class="filtros-pagos-secundarios">

                                <label>
                                    Desde
                                    <input type="date" name="fecha_desde"
                                        value="{{ request('fecha_desde') }}">
                                </label>

                                <label>
                                    Hasta
                                    <input type="date" name="fecha_hasta"
                                        value="{{ request('fecha_hasta') }}">
                                </label>

                                <label>
                                    Monto mínimo
                                    <input type="number" name="monto_min" min="0" step="0.01"
                                        value="{{ request('monto_min') }}" placeholder="$ 0">
                                </label>

                                <label>
                                    Monto máximo
                                    <input type="number" name="monto_max" min="0" step="0.01"
                                        value="{{ request('monto_max') }}" placeholder="$ 0">
                                </label>

                                <label>
                                    Estado
                                    <select name="estado">
                                        <option value="">Todos</option>
                                        <option value="Pendiente" @selected(request('estado') === 'Pendiente')>
                                            Pendiente
                                        </option>
                                        <option value="Verificado" @selected(request('estado') === 'Verificado')>
                                            Verificado
                                        </option>
                                        <option value="Rechazado" @selected(request('estado') === 'Rechazado')>
                                            Rechazado
                                        </option>
                                    </select>
                                </label>

                            </div>

                            <div class="acciones-filtros-pagos">
                                <button type="submit" class="btn-primario">
                                    Aplicar filtros
                                </button>

                                <a href="{{ route('pago.index') }}" class="btn-borde">
                                    Limpiar
                                </a>
                            </div>

                        </form>

                        {{-- TABLA --}}
                        <div class="tabla-contenedor-pagos">
                            <table class="tabla tabla-pagos">

                                <thead>
                                    <tr>
                                        <th>Usuario / suscripción</th>
                                        <th>Valor / fecha de pago</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @forelse ($pagos as $pago)

                                        <tr>

                                            <td>
                                                <span class="nombre">
                                                    {{ $pago->suscripcion->usuario->nombre }}
                                                    {{ $pago->suscripcion->usuario->apellido }}
                                                </span>

                                                <small>
                                                    {{ $pago->suscripcion->usuario->documento }}
                                                </small>

                                                <small class="plan-pago">
                                                    {{ $pago->suscripcion->plan->nombre_plan }}
                                                </small>
                                            </td>

                                            <td>
                                                <span class="valor-pago">
                                                    $ {{ number_format($pago->monto, 0, ',', '.') }}
                                                </span>

                                                <small class="oscuro">
                                                    {{ $pago->fecha_pago->format('d/m/Y · H:i') }}
                                                </small>
                                            </td>

                                            <td>
                                                @if ($pago->estado_pago === 'Pendiente')
                                                    <span class="estado-pago pendiente">
                                                        Pendiente
                                                    </span>
                                                @elseif ($pago->estado_pago === 'Verificado')
                                                    <span class="estado-pago verificado">
                                                        Verificado
                                                    </span>
                                                @else
                                                    <span class="estado-pago rechazado">
                                                        Rechazado
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($pago->estado_pago === 'Verificado')

                                                    <button type="button"
                                                        class="btn-revision desactivado"
                                                        disabled>
                                                        Verificado
                                                    </button>

                                                @elseif ($pago->estado_pago === 'Rechazado')

                                                    <button type="button"
                                                        class="btn-revision rechazado"
                                                        data-abrir-revision
                                                        data-action="{{ route('pago.revisar', $pago->pago_id) }}"
                                                        data-usuario="{{ $pago->suscripcion->usuario->nombre }} {{ $pago->suscripcion->usuario->apellido }}"
                                                        data-plan="{{ $pago->suscripcion->plan->nombre_plan }}"
                                                        data-monto="{{ number_format($pago->monto, 0, ',', '.') }}"
                                                        data-estado="Rechazado">
                                                        Revisar
                                                    </button>

                                                @else

                                                    <button type="button"
                                                        class="btn-revision pendiente"
                                                        data-abrir-revision
                                                        data-action="{{ route('pago.revisar', $pago->pago_id) }}"
                                                        data-usuario="{{ $pago->suscripcion->usuario->nombre }} {{ $pago->suscripcion->usuario->apellido }}"
                                                        data-plan="{{ $pago->suscripcion->plan->nombre_plan }}"
                                                        data-monto="{{ number_format($pago->monto, 0, ',', '.') }}"
                                                        data-estado="Pendiente">
                                                        Registrar revisión
                                                    </button>

                                                @endif
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>
                                            <td colspan="4" class="sin-pagos">
                                                No se encontraron pagos registrados.
                                            </td>
                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>
                        </div>

                        {{-- PAGINACIÓN --}}
                        <div class="paginacion-pagos">

                            <small>
                                @if ($pagos->total() > 0)
                                    Mostrando {{ $pagos->firstItem() }}–{{ $pagos->lastItem() }}
                                    de {{ $pagos->total() }} registros
                                @else
                                    No hay registros para mostrar
                                @endif
                            </small>

                            {{ $pagos->links() }}

                        </div>

                    </section>


                    {{-- ================================= --}}
                    {{-- FORMULARIO DE REGISTRO --}}
                    {{-- ================================= --}}

                    <aside class="tarjeta formulario-pago">

                        <h2>Registrar nuevo pago</h2>

                        <small class="tarjeta-sub">
                            Registra un pago asociado a una suscripción.
                        </small>

                        <form action="{{ route('pago.store') }}" method="POST" id="formNuevoPago">
                            @csrf

                            <div class="campo-pago">
                                <label for="suscripcion_id">Suscripción *</label>

                                <select name="suscripcion_id" id="suscripcion_id" required>
                                    <option value="">Selecciona una suscripción</option>

                                    @foreach ($suscripciones as $suscripcion)

                                        <option
                                            value="{{ $suscripcion->suscripcion_id }}"
                                            data-saldo="{{ $suscripcion->saldo_pendiente }}"
                                            @selected(old('suscripcion_id') == $suscripcion->suscripcion_id)>

                                            {{ $suscripcion->usuario->nombre }}
                                            {{ $suscripcion->usuario->apellido }}
                                            · {{ $suscripcion->usuario->documento }}
                                            · {{ $suscripcion->plan->nombre_plan }}

                                        </option>

                                    @endforeach

                                </select>
                            </div>

                            <div class="saldo-pago" id="saldoPago">
                                Selecciona una suscripción para consultar su saldo pendiente.
                            </div>

                            <div class="campo-pago">
                                <label for="metodo_pago_id">Método de pago *</label>

                                <select name="metodo_pago_id" id="metodo_pago_id" required>
                                    <option value="">Selecciona un método</option>

                                    @foreach ($metodosPago as $metodo)
                                        <option value="{{ $metodo->metodo_pago_id }}"
                                            @selected(old('metodo_pago_id') == $metodo->metodo_pago_id)>
                                            {{ $metodo->nombre_metodo }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                            <div class="campo-pago">
                                <label for="monto">Monto del pago (COP) *</label>

                                <input
                                    type="number"
                                    name="monto"
                                    id="monto"
                                    min="0.01"
                                    step="0.01"
                                    value="{{ old('monto') }}"
                                    placeholder="Ej. 35000"
                                    required>

                                <small id="ayudaMonto">
                                    El monto debe ser mayor que cero y no superar el saldo pendiente.
                                </small>
                            </div>

                            <div class="nota-registro-pago">
                                <strong>Importante</strong>
                                <p>
                                    La fecha de registro se asigna automáticamente y el pago
                                    queda inicialmente pendiente de verificación.
                                </p>
                            </div>

                            <button type="submit" class="btn-primario btn-registrar-pago">
                                Registrar pago
                            </button>

                        </form>

                    </aside>

                </div>

                <footer class="pie-pagina">
                    Institución Universitaria Pascual Bravo · SIGIP
                </footer>

            </main>
        </div>
    </div>


    {{-- ================================= --}}
    {{-- MODAL DE REVISIÓN --}}
    {{-- ================================= --}}

    <dialog class="modal-plan modal-revision" id="modalRevision">

        <div class="modal-plan-cuerpo">

            <div class="modal-plan-cab">
                <div>
                    <h2>Registrar revisión</h2>
                    <p>Verifica el soporte antes de registrar el resultado.</p>
                </div>

                <button class="modal-plan-cerrar" id="btnCerrarRevision"
                    type="button" aria-label="Cerrar">
                    ×
                </button>
            </div>

            <form id="formRevision" method="POST">
                @csrf
                @method('PATCH')

                <div class="resumen-revision">
                    <span>Usuario</span>
                    <strong id="revisionUsuario">—</strong>

                    <span>Suscripción</span>
                    <strong id="revisionPlan">—</strong>

                    <span>Valor del pago</span>
                    <strong id="revisionMonto">—</strong>

                    <span>Estado actual</span>
                    <strong id="revisionEstado">—</strong>
                </div>

                <div class="campo-pago">
                    <label for="estado_pago">Resultado de la revisión *</label>

                    <select name="estado_pago" id="estado_pago" required>
                        <option value="">Selecciona el resultado</option>
                        <option value="Verificado">Verificado</option>
                        <option value="Rechazado">Rechazado</option>
                    </select>
                </div>

                <p class="nota-plan">
                    Si verificas el pago, no podrás modificar su estado posteriormente.
                    Si lo rechazas, podrás realizar una nueva revisión.
                </p>

                <div class="modal-plan-acciones">
                    <button class="btn-borde" id="btnCancelarRevision" type="button">
                        Cancelar
                    </button>

                    <button class="btn-primario" type="submit">
                        Guardar revisión
                    </button>
                </div>

            </form>

        </div>

    </dialog>


    {{-- ================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // --------------------------------------
            // SALDO PENDIENTE DE LA SUSCRIPCIÓN
            // --------------------------------------

            const selectorSuscripcion = document.getElementById('suscripcion_id');
            const inputMonto = document.getElementById('monto');
            const saldoPago = document.getElementById('saldoPago');
            const ayudaMonto = document.getElementById('ayudaMonto');

            function actualizarSaldo() {

                const opcion = selectorSuscripcion.selectedOptions[0];

                if (!opcion || !opcion.value) {
                    saldoPago.textContent =
                        'Selecciona una suscripción para consultar su saldo pendiente.';

                    inputMonto.removeAttribute('max');
                    return;
                }

                const saldo = Number(opcion.dataset.saldo);

                saldoPago.textContent =
                    'Saldo pendiente: $ ' +
                    saldo.toLocaleString('es-CO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    });

                inputMonto.max = saldo.toFixed(2);

                ayudaMonto.textContent =
                    'Puedes registrar hasta $ ' +
                    saldo.toLocaleString('es-CO') +
                    ' como máximo.';

                inputMonto.setCustomValidity('');
            }

            selectorSuscripcion.addEventListener('change', actualizarSaldo);

            inputMonto.addEventListener('input', () => {

                const maximo = Number(inputMonto.max);

                if (inputMonto.value && Number(inputMonto.value) > maximo) {
                    inputMonto.setCustomValidity(
                        'El monto supera el saldo pendiente de la suscripción.'
                    );
                } else {
                    inputMonto.setCustomValidity('');
                }

            });

            actualizarSaldo();


            // --------------------------------------
            // MODAL DE REVISIÓN
            // --------------------------------------

            const modalRevision = document.getElementById('modalRevision');
            const formRevision = document.getElementById('formRevision');

            const btnCerrarRevision = document.getElementById('btnCerrarRevision');
            const btnCancelarRevision = document.getElementById('btnCancelarRevision');

            document.querySelectorAll('[data-abrir-revision]').forEach((boton) => {

                boton.addEventListener('click', () => {

                    formRevision.action = boton.dataset.action;

                    document.getElementById('revisionUsuario').textContent =
                        boton.dataset.usuario;

                    document.getElementById('revisionPlan').textContent =
                        boton.dataset.plan;

                    document.getElementById('revisionMonto').textContent =
                        '$ ' + boton.dataset.monto;

                    document.getElementById('revisionEstado').textContent =
                        boton.dataset.estado;

                    document.getElementById('estado_pago').value = '';

                    modalRevision.showModal();

                });

            });

            function cerrarModalRevision() {
                modalRevision.close();
            }

            btnCerrarRevision.addEventListener('click', cerrarModalRevision);
            btnCancelarRevision.addEventListener('click', cerrarModalRevision);

            modalRevision.addEventListener('click', (evento) => {
                if (evento.target === modalRevision) {
                    cerrarModalRevision();
                }
            });


            // --------------------------------------
            // OCULTAR MENSAJES
            // --------------------------------------

            const mensaje = document.getElementById('mensaje');
            const mensajeError = document.getElementById('mensaje-error');

            [mensaje, mensajeError].forEach((elemento) => {

                if (elemento) {
                    setTimeout(() => {
                        elemento.style.display = 'none';
                    }, 5000);
                }

            });

        });
    </script>

</body>
</html>
