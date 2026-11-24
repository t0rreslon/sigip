
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
    // 2. FILTROS DEL INVENTARIO (HU-41)
    // Los filtros se aplican en el servidor; los selectores envían el formulario.
    // ==========================================

    const formFiltros = document.getElementById('form-filtros-inventario');

    formFiltros?.querySelectorAll('[data-autoenviar]').forEach((selector) => {
        selector.addEventListener('change', () => formFiltros.requestSubmit());
    });
});
