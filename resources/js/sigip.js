/**
 * Comportamientos comunes de las páginas que usan layouts/sigip.blade.php.
 *
 *  - [data-abrir="idModal"]       abre un <dialog>.
 *      data-accion="url"          cambia el action del formulario del modal.
 *      data-campos='{"name": v}'  rellena los campos del formulario (por name).
 *      data-textos='{"id": "t"}'  pone textos en elementos del modal (por id).
 *  - [data-cerrar]                cierra el <dialog> que lo contiene.
 *  - [data-autoenviar]            envía su formulario al cambiar.
 *  - form[data-confirmar="msg"]   pide confirmación antes de enviar.
 */
function asegurarOculto(formulario, nombre, valor) {
    let campo = formulario.querySelector(`input[type="hidden"][name="${nombre}"]`);

    if (!campo) {
        campo = document.createElement('input');
        campo.type = 'hidden';
        campo.name = nombre;
        formulario.appendChild(campo);
    }

    campo.value = valor;
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-abrir]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const modal = document.getElementById(boton.dataset.abrir);

            if (!modal) return;

            const formulario = modal.querySelector('form');

            if (formulario && boton.dataset.accion) {
                formulario.action = boton.dataset.accion;
            }

            // Datos ocultos para reabrir el mismo modal si el servidor devuelve errores.
            if (formulario) {
                asegurarOculto(formulario, '_modal', modal.id);
                asegurarOculto(formulario, '_accion', formulario.action);
            }

            if (formulario && boton.dataset.campos) {
                const campos = JSON.parse(boton.dataset.campos);

                Object.entries(campos).forEach(([nombre, valor]) => {
                    const elementos = formulario.querySelectorAll(`[name="${nombre}"]`);

                    elementos.forEach((elemento) => {
                        // El oculto que acompaña a un checkbox conserva su valor por defecto ("0").
                        if (elemento.type === 'hidden' && elementos.length > 1) {
                            return;
                        }

                        if (elemento.type === 'checkbox' || elemento.type === 'radio') {
                            elemento.checked = String(elemento.value) === String(valor) || valor === true;
                        } else {
                            elemento.value = valor ?? '';
                        }
                    });
                });
            }

            if (boton.dataset.textos) {
                Object.entries(JSON.parse(boton.dataset.textos)).forEach(([id, texto]) => {
                    const elemento = document.getElementById(id);

                    if (elemento) elemento.textContent = texto ?? '';
                });
            }

            modal.showModal();
        });
    });

    document.querySelectorAll('[data-cerrar]').forEach((boton) => {
        boton.addEventListener('click', () => boton.closest('dialog')?.close());
    });

    document.querySelectorAll('dialog').forEach((modal) => {
        modal.addEventListener('click', (evento) => {
            if (evento.target === modal) modal.close();
        });
    });

    document.querySelectorAll('[data-autoenviar]').forEach((campo) => {
        campo.addEventListener('change', () => campo.form?.requestSubmit());
    });

    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
});
