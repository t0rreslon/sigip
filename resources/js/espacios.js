document.addEventListener('DOMContentLoaded', () => {

    // Cerrar cualquier modal con los botones [data-cerrar] o al hacer clic afuera.
    document.querySelectorAll('[data-cerrar]').forEach((boton) => {
        boton.addEventListener('click', () => boton.closest('dialog')?.close());
    });

    document.querySelectorAll('dialog').forEach((modal) => {
        modal.addEventListener('click', (evento) => {
            if (evento.target === modal) modal.close();
        });
    });

    // Los selectores con [data-autoenviar] aplican el filtro al cambiar.
    document.querySelectorAll('[data-autoenviar]').forEach((selector) => {
        selector.addEventListener('change', () => selector.form?.requestSubmit());
    });

    // HU-45: registrar espacio.
    document.getElementById('abrir-registrar-espacio')?.addEventListener('click', () => {
        document.getElementById('modal-registrar-espacio')?.showModal();
    });

    // HU-47: deshabilitar espacio (pide el motivo).
    const modalDeshabilitar = document.getElementById('modal-deshabilitar-espacio');
    const formDeshabilitar = document.getElementById('form-deshabilitar-espacio');

    document.querySelectorAll('.btn-deshabilitar-espacio').forEach((boton) => {
        boton.addEventListener('click', () => {
            if (!modalDeshabilitar || !formDeshabilitar) return;

            formDeshabilitar.action = boton.dataset.accion;
            document.getElementById('nombre-espacio-deshabilitar').textContent = boton.dataset.nombre;
            modalDeshabilitar.showModal();
        });
    });

    // HU-49: registrar reserva.
    document.getElementById('abrir-registrar-reserva')?.addEventListener('click', () => {
        document.getElementById('modal-registrar-reserva')?.showModal();
    });

    // Reservar directamente desde la tarjeta de un espacio disponible.
    document.querySelectorAll('.btn-reservar-espacio').forEach((boton) => {
        boton.addEventListener('click', () => {
            const modal = document.getElementById('modal-registrar-reserva');
            const selector = document.getElementById('reserva-espacio');

            if (selector) selector.value = boton.dataset.espacio;
            modal?.showModal();
        });
    });

    // Confirmación antes de cancelar una reserva.
    document.querySelectorAll('.form-cancelar-reserva').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm('¿Cancelar esta reserva? El horario quedará disponible nuevamente.')) {
                evento.preventDefault();
            }
        });
    });
});
