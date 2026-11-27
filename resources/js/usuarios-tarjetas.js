/**
 * Usuarios en tarjetas:
 * - "Ver" abre un pop-up con la ficha completa en una tabla y una pestaña de historial
 *   que se carga dentro del mismo pop-up (sin salir de la página).
 * - Vista previa de la foto en los formularios de registro y edición.
 */

function avatar(contenedor, foto, iniciales) {
    contenedor.replaceChildren();

    if (foto) {
        const img = document.createElement('img');
        img.src = foto;
        img.alt = '';
        contenedor.appendChild(img);
    } else {
        const span = document.createElement('span');
        span.className = 'avatar-iniciales';
        span.textContent = iniciales || '+';
        contenedor.appendChild(span);
    }
}

// ---------- Vista previa de la foto elegida ----------
document.querySelectorAll('input[type=file][data-foto]').forEach((entrada) => {
    entrada.addEventListener('change', () => {
        const vista = document.querySelector(`[data-vista-foto="${entrada.id}"]`);
        const archivo = entrada.files?.[0];

        if (!vista || !archivo || !archivo.type.startsWith('image/')) {
            return;
        }

        avatar(vista, URL.createObjectURL(archivo));
    });
});

// Lo llama el script de edición al abrir el pop-up: muestra la foto actual del usuario.
window.sigipMostrarFotoEdicion = (foto, iniciales) => {
    const vista = document.getElementById('editar_vista_foto');
    const entrada = document.getElementById('editar_imagen');
    const quitar = document.getElementById('editar_quitar_foto_contenedor');

    if (entrada) entrada.value = '';
    if (vista) avatar(vista, foto || null, iniciales);
    if (quitar) {
        quitar.hidden = !foto;
        quitar.querySelector('input').checked = false;
    }
};

// ---------- Pop-up "Ver usuario" ----------
const modalVer = document.getElementById('modalVerUsuario');

if (modalVer) {
    const cuerpoDatos = document.getElementById('verUsuarioDatos');
    const historial = document.getElementById('verUsuarioHistorial');
    const formEstado = document.getElementById('verUsuarioFormEstado');
    const btnEstado = document.getElementById('verUsuarioBtnEstado');
    let fichaActual = null;
    let historialCargado = null;

    const mostrarPestana = (nombre) => {
        modalVer.querySelectorAll('[data-pestana-ver]').forEach((boton) => {
            const activa = boton.dataset.pestanaVer === nombre;
            boton.classList.toggle('activa', activa);
            boton.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
        modalVer.querySelectorAll('[data-panel-ver]').forEach((panel) => {
            panel.hidden = panel.dataset.panelVer !== nombre;
        });

        if (nombre === 'historial') {
            cargarHistorial();
        }
    };

    // El historial se toma de la página de historial del usuario y se muestra aquí mismo.
    const cargarHistorial = async () => {
        if (!fichaActual || historialCargado === fichaActual.id) {
            return;
        }

        historial.textContent = 'Cargando historial…';

        try {
            const respuesta = await fetch(fichaActual.historial, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });

            if (!respuesta.ok) throw new Error(respuesta.status);

            const pagina = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
            const tabla = pagina.querySelector('main table');

            historial.replaceChildren(tabla ? document.importNode(tabla, true) : document.createTextNode('No hay cambios registrados.'));
            historialCargado = fichaActual.id;
        } catch {
            historial.textContent = 'No fue posible cargar el historial. Inténtalo de nuevo.';
        }
    };

    document.querySelectorAll('.btn-ver-usuario').forEach((boton) => {
        boton.addEventListener('click', () => {
            fichaActual = JSON.parse(boton.dataset.ficha);

            avatar(document.getElementById('verUsuarioFoto'), fichaActual.foto, fichaActual.iniciales);
            document.getElementById('verUsuarioNombre').textContent = fichaActual.nombre;
            document.getElementById('verUsuarioEstado').textContent = fichaActual.activo ? 'Usuario activo' : 'Usuario inactivo';

            cuerpoDatos.replaceChildren(...Object.entries(fichaActual.datos).map(([campo, valor]) => {
                const fila = document.createElement('tr');
                const th = document.createElement('th');
                const td = document.createElement('td');
                th.scope = 'row';
                th.textContent = campo;
                td.textContent = valor ?? '—';
                fila.append(th, td);
                return fila;
            }));

            formEstado.action = fichaActual.estadoAccion;
            btnEstado.textContent = fichaActual.activo ? 'Desactivar usuario' : 'Activar usuario';
            formEstado.dataset.confirmar = `¿${fichaActual.activo ? 'Desactivar' : 'Activar'} a ${fichaActual.nombre}?`;

            historialCargado = null;
            mostrarPestana('info');
            modalVer.showModal();
        });
    });

    modalVer.querySelectorAll('[data-pestana-ver]').forEach((boton) => {
        boton.addEventListener('click', () => mostrarPestana(boton.dataset.pestanaVer));
    });

    modalVer.querySelectorAll('[data-cerrar-ver]').forEach((boton) => {
        boton.addEventListener('click', () => modalVer.close());
    });

    // "Editar" desde la ficha: cierra el pop-up y abre el de edición del mismo usuario.
    document.getElementById('verUsuarioEditar')?.addEventListener('click', () => {
        modalVer.close();
        document.querySelector(`.btn-editar-usuario[data-id="${fichaActual.id}"]`)?.click();
    });
}
