import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const selectorCategoria = document.getElementById('categoria_implemento');
    const listaImplementos = document.getElementById('listaImplementos');

    if (!selectorCategoria || !listaImplementos) {
        return;
    }

    const implementos = listaImplementos.querySelectorAll(
        '.implemento-opcion'
    );

    // Si no hay implementos registrados como disponibles.
    if (implementos.length === 0) {
        return;
    }

    // Crear el mensaje para cuando una categoría no tenga implementos.
    const mensaje = document.createElement('p');
    mensaje.classList.add('mensaje-implementos');
    mensaje.textContent = 'No hay implementos disponibles en esta categoría.';
    mensaje.style.display = 'none';

    listaImplementos.appendChild(mensaje);

    selectorCategoria.addEventListener('change', () => {
        const categoriaSeleccionada = selectorCategoria.value;
        let cantidadVisibles = 0;

        implementos.forEach((implemento) => {
            const categoriaImplemento = implemento.dataset.categoria;

            const coincide =
                categoriaSeleccionada === '' ||
                categoriaImplemento === categoriaSeleccionada;

            implemento.style.display = coincide ? '' : 'none';

            if (coincide) {
                cantidadVisibles++;
            }
        });

        // Mostrar u ocultar el mensaje según los resultados.
        mensaje.style.display =
            cantidadVisibles === 0 ? 'block' : 'none';
    });
});

