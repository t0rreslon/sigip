// FILTRO DE CATEGORÍAS
function inicializarFiltroCategorias() {
    const selectorCategoria = document.getElementById('categoria_implemento');
    const listaImplementos = document.getElementById('listaImplementos');

    if (!selectorCategoria || !listaImplementos) return;

    selectorCategoria.addEventListener('change', function () {
        const categoriaSeleccionada = this.value;

        const implementos = listaImplementos.querySelectorAll(
            '.implemento-opcion'
        );

        implementos.forEach(function (implemento) {
            const categoriaImplemento = implemento.dataset.categoria;

            const mostrar =
                categoriaSeleccionada === '' ||
                categoriaImplemento === categoriaSeleccionada;

            implemento.style.display = mostrar ? '' : 'none';
        });
    });
}


// CARRUSEL MANUAL DE CATEGORÍAS
function inicializarCarruselCategorias() {
    const carrusel = document.querySelector('.disponibilidad-elementos');
    const track = document.getElementById('prestamos-track');
    const anterior = document.getElementById('prestamos-anterior');
    const siguiente = document.getElementById('prestamos-siguiente');

    if (!carrusel || !track || !anterior || !siguiente) return;

    let desplazamiento = 0;

    function obtenerPaso() {
        const tarjeta = track.querySelector('.tarjeta-metrica');

        if (!tarjeta) return 0;

        const espacio = parseFloat(getComputedStyle(track).gap) || 0;

        return tarjeta.getBoundingClientRect().width + espacio;
    }

    function actualizarCarrusel() {
        const limite = Math.max(
            0,
            track.scrollWidth - carrusel.clientWidth
        );

        desplazamiento = Math.min(desplazamiento, limite);

        track.style.transform = `translateX(-${desplazamiento}px)`;

        anterior.disabled = desplazamiento <= 0;
        siguiente.disabled = desplazamiento >= limite - 1;
    }

    siguiente.addEventListener('click', () => {
        desplazamiento += obtenerPaso();
        actualizarCarrusel();
    });

    anterior.addEventListener('click', () => {
        desplazamiento -= obtenerPaso();
        actualizarCarrusel();
    });

    window.addEventListener('resize', actualizarCarrusel);

    actualizarCarrusel();
}


// FILTROS Y CALENDARIO DE PRÉSTAMOS
function inicializarFiltroFecha() {
    const formulario = document.getElementById('formFiltros');
    const botonCalendario = document.getElementById('abrirCalendario');
    const fechaExacta = document.getElementById('fechaExacta');

    if (!formulario) return;

    // Aplicar filtros automáticamente al cambiar.
    const filtros = formulario.querySelectorAll(
        'select[name="tipo"], ' +
        'select[name="categoria"], ' +
        'select[name="estado"], ' +
        'select[name="periodo"]'
    );

    filtros.forEach((filtro) => {
        filtro.addEventListener('change', () => {
            formulario.requestSubmit();
        });
    });

    // Abrir calendario.
    if (botonCalendario && fechaExacta) {
        botonCalendario.addEventListener('click', () => {
            if (typeof fechaExacta.showPicker === 'function') {
                fechaExacta.showPicker();
            } else {
                fechaExacta.click();
            }
        });

        // Aplicar filtro al seleccionar una fecha.
        fechaExacta.addEventListener('change', () => {
            formulario.requestSubmit();
        });
    }
}


// INICIALIZAR FUNCIONES
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        inicializarFiltroCategorias();
        inicializarCarruselCategorias();
        inicializarFiltroFecha();
    });
} else {
    inicializarFiltroCategorias();
    inicializarCarruselCategorias();
    inicializarFiltroFecha();
}