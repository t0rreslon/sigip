
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
    // 5. OCULTAR MENSAJE DESPUÉS DE 4 SEGUNDOS
    // =====================================================

    const mensaje = document.getElementById('mensaje');

    if (mensaje) {
        setTimeout(() => {
            mensaje.style.display = 'none';
        }, 4000);
    }

});
