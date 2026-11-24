
document.addEventListener('DOMContentLoaded', () => {

    // =====================================================
    // 1. CARRUSEL DE PLANES
    // =====================================================

    const track = document.getElementById('planes-track');
    const anterior = document.getElementById('planes-anterior');
    const siguiente = document.getElementById('planes-siguiente');

    if (track && anterior && siguiente) {
        let desplazamiento = 0;

        const obtenerDesplazamiento = () => {
            const tarjeta = track.querySelector('.tarjeta-plan');

            if (!tarjeta) return 0;

            const espacio = parseFloat(getComputedStyle(track).gap) || 0;

            return tarjeta.getBoundingClientRect().width + espacio;
        };

        const actualizarFlechas = () => {
            const anchoVisible = track.parentElement.clientWidth;
            const limite = Math.max(0, track.scrollWidth - anchoVisible);

            anterior.disabled = desplazamiento <= 0;
            siguiente.disabled = desplazamiento >= limite - 1;
        };

        siguiente.addEventListener('click', () => {
            const paso = obtenerDesplazamiento();
            const anchoVisible = track.parentElement.clientWidth;
            const limite = Math.max(0, track.scrollWidth - anchoVisible);

            desplazamiento = Math.min(desplazamiento + paso, limite);

            track.style.transform = `translateX(-${desplazamiento}px)`;

            actualizarFlechas();
        });

        anterior.addEventListener('click', () => {
            const paso = obtenerDesplazamiento();

            desplazamiento = Math.max(0, desplazamiento - paso);

            track.style.transform = `translateX(-${desplazamiento}px)`;

            actualizarFlechas();
        });

        window.addEventListener('resize', () => {
            const anchoVisible = track.parentElement.clientWidth;
            const limite = Math.max(0, track.scrollWidth - anchoVisible);

            desplazamiento = Math.min(desplazamiento, limite);

            track.style.transform = `translateX(-${desplazamiento}px)`;

            actualizarFlechas();
        });

        actualizarFlechas();
    }


    // =====================================================
    // 2. APERTURA Y CIERRE DEL MODAL DE CREACIÓN
    // =====================================================

    const modalCrear = document.getElementById('modal-crear-plan');
    const btnAbrirCrear = document.getElementById('abrir-crear-plan');
    const formCrear = document.getElementById('form-crear-plan');

    if (modalCrear && btnAbrirCrear && formCrear) {

        btnAbrirCrear.addEventListener('click', () => {
            formCrear.reset();
            actualizarEtiquetaCantidad();
            modalCrear.showModal();
        });
    }


    // =====================================================
    // 3. CIERRE DE LOS MODALES
    // =====================================================

    document.querySelectorAll('[data-cerrar]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const dialogo = boton.closest('dialog');

            if (dialogo) {
                dialogo.close();
            }
        });
    });

    document.querySelectorAll('dialog').forEach((modal) => {
        modal.addEventListener('click', (evento) => {
            if (evento.target === modal) {
                modal.close();
            }
        });
    });


    // =====================================================
    // 4. CAMBIAR ETIQUETA SEGÚN EL TIPO DE PLAN
    // =====================================================

    const tipoPlanCrear = document.getElementById('tipo-plan-crear');
    const etiquetaCantidadCrear = document.getElementById('etiqueta-cantidad-crear');

    function actualizarEtiquetaCantidad() {
        if (!tipoPlanCrear || !etiquetaCantidadCrear) return;

        if (tipoPlanCrear.value === 'tiquetera') {
            etiquetaCantidadCrear.textContent = 'Cantidad de entradas *';
        } else {
            etiquetaCantidadCrear.textContent = 'Días de vigencia *';
        }
    }

    if (tipoPlanCrear) {
        tipoPlanCrear.addEventListener('change', actualizarEtiquetaCantidad);
        actualizarEtiquetaCantidad();
    }



    // =====================================================
    // 5. CONSULTAR / EDITAR PLAN (HU-34, HU-36)
    // =====================================================

    const modalEditar = document.getElementById('modal-editar-plan');
    const formEditar = document.getElementById('form-editar-plan');
    const tipoPlanEditar = document.getElementById('editar-tipo-plan');
    const etiquetaCantidadEditar = document.getElementById('etiqueta-cantidad-editar');

    function actualizarEtiquetaEditar() {
        if (!tipoPlanEditar || !etiquetaCantidadEditar) return;

        etiquetaCantidadEditar.textContent = tipoPlanEditar.value === 'tiquetera'
            ? 'Cantidad de entradas *'
            : 'Días de vigencia *';
    }

    tipoPlanEditar?.addEventListener('change', actualizarEtiquetaEditar);
    actualizarEtiquetaEditar();

    document.querySelectorAll('.btn-editar-plan').forEach((boton) => {
        boton.addEventListener('click', () => {
            if (!modalEditar || !formEditar) return;

            const datos = boton.dataset;

            formEditar.action = `${formEditar.dataset.accionBase}/${datos.id}`;
            document.getElementById('editar-plan-id').value = datos.id;
            document.getElementById('editar-nombre-plan').value = datos.nombre || '';
            document.getElementById('editar-cantidad-plan').value = datos.cantidad || '';
            document.getElementById('editar-costo-plan').value = datos.precio || '';
            document.getElementById('editar-descripcion-plan').value = datos.descripcion || '';
            tipoPlanEditar.value = datos.tipo || 'tiquetera';
            actualizarEtiquetaEditar();

            document.getElementById('editar-plan-sub').textContent =
                `${datos.nombre} · ${datos.tipo === 'tiquetera' ? 'Tiquetera' : 'Suscripción'}`;

            modalEditar.querySelectorAll('[data-detalle]').forEach((celda) => {
                celda.textContent = datos[celda.dataset.detalle] ?? '—';
            });

            formEditar.querySelectorAll('.invalid-feedback').forEach((error) => error.remove());
            modalEditar.showModal();
        });
    });


    // =====================================================
    // 6. FILTROS DE SUSCRIPCIONES
    // =====================================================

    const formFiltros = document.getElementById('form-filtros-suscripciones');

    formFiltros?.querySelectorAll('[data-autoenviar]').forEach((selector) => {
        selector.addEventListener('change', () => formFiltros.requestSubmit());
    });


    // =====================================================
    // 7. ASIGNAR SUSCRIPCIÓN
    // =====================================================

    const modalAsignar = document.getElementById('modal-asignar-suscripcion');
    const selectorPlanAsignar = document.getElementById('asignar-plan');
    const campoFinAsignar = document.getElementById('campo-asignar-fin');

    function actualizarCampoFinAsignar() {
        if (!selectorPlanAsignar || !campoFinAsignar) return;

        const opcion = selectorPlanAsignar.selectedOptions[0];
        const esSuscripcion = opcion?.dataset.tipo === 'suscripcion';

        campoFinAsignar.hidden = esSuscripcion;
        campoFinAsignar.querySelector('input').disabled = esSuscripcion;
    }

    selectorPlanAsignar?.addEventListener('change', actualizarCampoFinAsignar);
    actualizarCampoFinAsignar();

    document.getElementById('abrir-asignar-suscripcion')?.addEventListener('click', () => {
        modalAsignar?.showModal();
    });


    // =====================================================
    // 8. MODIFICAR SUSCRIPCIÓN (HU-37, HU-39)
    // =====================================================

    const modalSuscripcion = document.getElementById('modal-editar-suscripcion');
    const formSuscripcion = document.getElementById('form-editar-suscripcion');
    const selectorPlanSuscripcion = document.getElementById('editar-suscripcion-plan');
    const campoFin = document.getElementById('editar-suscripcion-fin');
    const campoEntradas = document.getElementById('editar-suscripcion-entradas');
    const etiquetaEntradas = document.getElementById('etiqueta-entradas-suscripcion');
    const campoInicio = document.getElementById('editar-suscripcion-inicio');

    function actualizarEtiquetaEntradas() {
        const opcion = selectorPlanSuscripcion?.selectedOptions[0];

        if (!opcion || !etiquetaEntradas) return;

        etiquetaEntradas.textContent = opcion.dataset.tipo === 'tiquetera'
            ? 'Entradas restantes *'
            : 'Días de vigencia del plan *';

        campoFin.required = opcion.dataset.tipo === 'suscripcion';
        campoFin.min = campoInicio?.value || '';
    }

    // Al cambiar de plan se proponen las entradas y el vencimiento del nuevo plan.
    selectorPlanSuscripcion?.addEventListener('change', () => {
        const opcion = selectorPlanSuscripcion.selectedOptions[0];

        if (!opcion) return;

        campoEntradas.value = opcion.dataset.cantidad;

        if (opcion.dataset.tipo === 'suscripcion' && campoInicio?.value) {
            const fin = new Date(`${campoInicio.value}T12:00:00`);
            fin.setDate(fin.getDate() + Number(opcion.dataset.cantidad) - 1);
            campoFin.value = fin.toISOString().slice(0, 10);
        }

        actualizarEtiquetaEntradas();
    });

    document.querySelectorAll('.btn-editar-suscripcion').forEach((boton) => {
        boton.addEventListener('click', () => {
            if (!modalSuscripcion || !formSuscripcion) return;

            const datos = boton.dataset;

            formSuscripcion.action = `${formSuscripcion.dataset.accionBase}/${datos.id}`;
            document.getElementById('editar-suscripcion-id').value = datos.id;
            document.getElementById('editar-suscripcion-texto').value = datos.usuario;
            document.getElementById('editar-suscripcion-usuario').textContent = datos.usuario;
            campoInicio.value = datos.inicio;

            // El plan actual se puede conservar aunque esté inactivo.
            Array.from(selectorPlanSuscripcion.options).forEach((opcion) => {
                opcion.disabled = opcion.dataset.activo !== '1' && opcion.value !== datos.plan;
            });

            selectorPlanSuscripcion.value = datos.plan;
            campoFin.value = datos.fin || '';
            campoEntradas.value = datos.entradas;
            actualizarEtiquetaEntradas();

            formSuscripcion.querySelectorAll('.invalid-feedback').forEach((error) => error.remove());
            modalSuscripcion.showModal();
        });
    });

    actualizarEtiquetaEntradas();
});
