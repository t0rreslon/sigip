/**
 * Planes y suscripciones: pop-ups con los registros de planes y de suscripciones.
 * - Los botones [data-abrir-registros] abren el pop-up indicado.
 * - "Editar" de un plan reutiliza el botón de su tarjeta del carrusel (mismo formulario de edición).
 * - Si la página se carga con ?ver=tiqueteras o ?ver=suscripciones (al filtrar o paginar), ese pop-up se abre solo.
 */
document.querySelectorAll('[data-abrir-registros]').forEach((boton) => {
    boton.addEventListener('click', () => {
        document.getElementById(boton.dataset.abrirRegistros)?.showModal();
    });
});

document.querySelectorAll('[data-editar-plan-id]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const original = document.querySelector(`.btn-editar-plan[data-id="${boton.dataset.editarPlanId}"]`);

        if (original) {
            boton.closest('dialog')?.close();
            original.click();
        }
    });
});

const ver = new URLSearchParams(window.location.search).get('ver');

if (ver === 'suscripciones' || ver === 'tiqueteras') {
    const dialogo = document.getElementById(`modal-registros-${ver}`);

    // Si hay otro pop-up abierto por errores de validación, ese tiene prioridad.
    if (dialogo && !document.querySelector('dialog[open]')) {
        dialogo.showModal();
    }
}
