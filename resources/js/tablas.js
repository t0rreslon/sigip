/**
 * Tablas con desplazamiento horizontal: cada <table> queda dentro de un contenedor
 * que muestra una barra de desplazamiento solo cuando la tabla no cabe
 * (por ejemplo, al acercar la página con Ctrl + o en pantallas angostas).
 * Se carga desde el panel izquierdo, que está en todas las páginas del sistema.
 */
function envolverTablas(raiz = document) {
    raiz.querySelectorAll('table').forEach((tabla) => {
        const padre = tabla.parentElement;

        if (!padre || padre.classList.contains('tabla-desplazable') || tabla.closest('dialog, .tabla-desplazable')) {
            return;
        }

        // Un contenedor que ya desplaza (como el de usuarios) se aprovecha tal cual.
        if (padre.children.length === 1 && ['auto', 'scroll'].includes(getComputedStyle(padre).overflowX)) {
            padre.classList.add('tabla-desplazable');
            return;
        }

        const envoltura = document.createElement('div');
        envoltura.className = 'tabla-desplazable';
        padre.insertBefore(envoltura, tabla);
        envoltura.appendChild(tabla);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => envolverTablas());
} else {
    envolverTablas();
}
