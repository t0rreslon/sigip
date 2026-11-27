/**
 * Actividades: pop-up "Gestionar catálogo" (abrir, cerrar, buscar y filtrar por estado).
 * Los botones "Editar" y "+ Registrar actividad" usan el mecanismo data-abrir de sigip.js.
 */
const modalCatalogo = document.getElementById('modal-catalogo');

if (modalCatalogo) {
    const buscar = document.getElementById('buscar-catalogo');
    const estado = document.getElementById('estado-catalogo');
    const filas = [...modalCatalogo.querySelectorAll('tr[data-catalogo-nombre]')];
    const sinResultados = document.getElementById('catalogo-sin-resultados');

    const filtrar = () => {
        const texto = buscar.value.trim().toLowerCase();
        let visibles = 0;

        filas.forEach((fila) => {
            const coincide = (!texto || fila.dataset.catalogoNombre.includes(texto))
                && (!estado.value || fila.dataset.catalogoEstado === estado.value);
            fila.hidden = !coincide;
            visibles += coincide ? 1 : 0;
        });

        sinResultados.hidden = visibles > 0;
    };

    buscar.addEventListener('input', filtrar);
    estado.addEventListener('change', filtrar);

    document.querySelectorAll('[data-abrir-catalogo]').forEach((boton) => {
        boton.addEventListener('click', () => modalCatalogo.showModal());
    });

    modalCatalogo.querySelectorAll('[data-cerrar-catalogo]').forEach((boton) => {
        boton.addEventListener('click', () => modalCatalogo.close());
    });

    // Al editar o registrar desde el catálogo, se cierra para mostrar el formulario encima de la página.
    modalCatalogo.querySelectorAll('[data-abrir="modal-actividad"]').forEach((boton) => {
        boton.addEventListener('click', () => modalCatalogo.close());
    });
}
