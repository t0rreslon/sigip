
document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. ELEMENTOS DEL MODAL
    // ==========================================

    const modal = document.getElementById('modal-registrar-elemento');

    if (modal) {
        const form = modal.querySelector('form');
        const botonAbrir = document.getElementById('abrir-registrar-elemento');

        const tipo = document.getElementById('tipo-elemento');

        const categoriaMaquina = document.getElementById('categoria-maquina');
        const categoriaImplemento = document.getElementById('categoria-implemento');

        const estadoMaquina = document.getElementById('estado-maquina');
        const estadoImplemento = document.getElementById('estado-implemento');

        const filaUbicacion = document.getElementById('fila-ubicacion');
        const espacio = document.getElementById('espacio-elemento');

        function actualizarFormulario() {
            const esMaquina = tipo.value === 'maquina';

            // Categorías
            categoriaMaquina.disabled = !esMaquina;
            categoriaMaquina.hidden = !esMaquina;
            categoriaMaquina.required = esMaquina;

            categoriaImplemento.disabled = esMaquina;
            categoriaImplemento.hidden = esMaquina;
            categoriaImplemento.required = !esMaquina;

            // Estados
            estadoMaquina.disabled = !esMaquina;
            estadoMaquina.hidden = !esMaquina;
            estadoMaquina.required = esMaquina;

            estadoImplemento.disabled = esMaquina;
            estadoImplemento.hidden = esMaquina;
            estadoImplemento.required = !esMaquina;

            // Ubicación: solo máquinas
            filaUbicacion.hidden = !esMaquina;
            espacio.disabled = !esMaquina;
            espacio.required = esMaquina;
        }

        botonAbrir?.addEventListener('click', () => {
            form.reset();
            actualizarFormulario();
            modal.showModal();
        });

        tipo?.addEventListener('change', actualizarFormulario);

        modal.querySelectorAll('[data-cerrar]').forEach((boton) => {
            boton.addEventListener('click', () => {
                modal.close();
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.close();
            }
        });

        actualizarFormulario();
    }


    // ==========================================
    // 2. FILTRADO DE PESTAÑAS Y COLUMNAS
    // ==========================================

    const botonesFiltro = document.querySelectorAll('.pestanas button');
    const tabla = document.querySelector('table.tabla');
    const buscador = document.getElementById('buscar-inventario');

    const filas = tabla
        ? tabla.querySelectorAll('tbody tr[data-tipo]')
        : [];

    let filtroActual = 'todos';

    function actualizarColumnasInventario(tipo) {
        if (!tabla) return;

        tabla.classList.toggle('solo-maquinas', tipo === 'maquina');
    }

    function aplicarFiltros() {
        const busqueda = buscador
            ? buscador.value.trim().toLowerCase()
            : '';

        // Actualizar pestaña activa
        botonesFiltro.forEach((boton) => {
            boton.classList.toggle(
                'activa',
                boton.dataset.filtro === filtroActual
            );
        });

        // Filtrar elementos
        filas.forEach((fila) => {
            const tipoFila = fila.dataset.tipo;

            const coincideTipo =
                filtroActual === 'todos' || tipoFila === filtroActual;

            const contenidoFila = fila.textContent.toLowerCase();

            const coincideBusqueda =
                contenidoFila.includes(busqueda);

            fila.hidden = !(coincideTipo && coincideBusqueda);
        });

        actualizarColumnasInventario(filtroActual);
    }

    // Eventos de las pestañas
    botonesFiltro.forEach((boton) => {
        boton.addEventListener('click', () => {
            filtroActual = boton.dataset.filtro;
            aplicarFiltros();
        });
    });

    // Evento del buscador
    buscador?.addEventListener('input', aplicarFiltros);

    // Aplicar filtros iniciales
    const botonActivo = document.querySelector('.pestanas button.activa');

    filtroActual = botonActivo?.dataset.filtro || 'todos';

    aplicarFiltros();
    
});
