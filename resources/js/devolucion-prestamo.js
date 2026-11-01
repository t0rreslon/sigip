document.addEventListener('DOMContentLoaded', () => {
    // 1. Obtener la referencia al modal
    const modal = document.getElementById('modalDevolucion');
    
    // 2. Seleccionar TODOS los botones con la clase 'registrar-devolucion'
    const botonesDevolucion = document.querySelectorAll('.registrar-devolucion');
    
    // 3. Seleccionar los botones para cerrar (por ID o clase)
    const btnCerrar = document.getElementById('btnCerrarDev');
    const btnCancelar = document.getElementById('btnCancelarDev');

    // 4. Iterar sobre la lista de botones y asignarle a cada uno la apertura del modal
    botonesDevolucion.forEach(boton => {
        boton.addEventListener('click', () => {
            if (modal) {
                modal.showModal(); // Abre el diálogo nativo <dialog>
            }
        });
    });

    // 5. Asignar eventos de cierre
    if (btnCerrar) {
        btnCerrar.addEventListener('click', () => modal.close());
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', () => modal.close());
    }
});