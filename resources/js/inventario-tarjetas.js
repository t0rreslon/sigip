/**
 * Inventario en tarjetas por categoría:
 * - "Ver" abre un pop-up con las unidades de la categoría (cada una con su detalle).
 * - "Editar" abre el formulario de la categoría (nombre, descripción, stock mínimo y foto).
 */
const modalUnidades = document.getElementById('modal-unidades-categoria');
const modalEditar = document.getElementById('modal-editar-categoria');

const celda = (texto, clase) => {
    const td = document.createElement('td');
    if (clase) td.className = clase;
    td.textContent = texto ?? '—';
    return td;
};

document.querySelectorAll('[data-ver-categoria]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const datos = JSON.parse(boton.dataset.verCategoria);
        const esMaquina = datos.tipo === 'maquina';
        const cuerpo = document.getElementById('cuerpo-unidades-categoria');

        document.getElementById('titulo-unidades-categoria').textContent = datos.nombre;
        document.getElementById('subtitulo-unidades-categoria').textContent =
            `${datos.unidades.length} ${esMaquina ? 'máquina(s)' : 'implemento(s)'}`;
        modalUnidades.classList.toggle('con-maquinas', esMaquina);

        const filas = datos.unidades.map((u) => {
            const tr = document.createElement('tr');
            const estado = document.createElement('td');
            const etiqueta = document.createElement('span');
            etiqueta.className = `etiqueta-estado estado-${u.clase}`;
            etiqueta.textContent = u.estado;
            estado.appendChild(etiqueta);

            const acciones = document.createElement('td');
            const enlace = document.createElement('a');
            enlace.href = u.url;
            enlace.className = 'btn-borde';
            enlace.textContent = 'Ver / editar';
            acciones.appendChild(enlace);

            tr.append(celda(u.codigo), celda(u.nombre), estado, celda(u.ubicacion, 'solo-maquina'), celda(u.mantenimiento ?? 'Sin registro', 'solo-maquina'), acciones);
            return tr;
        });

        if (filas.length === 0) {
            const tr = document.createElement('tr');
            const td = celda('Esta categoría no tiene unidades con los filtros actuales.');
            td.colSpan = 6;
            tr.appendChild(td);
            filas.push(tr);
        }

        cuerpo.replaceChildren(...filas);
        modalUnidades.showModal();
    });
});

const vistaFoto = (contenedor, url) => {
    contenedor.replaceChildren();
    if (url) {
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        contenedor.appendChild(img);
    } else {
        const span = document.createElement('span');
        span.className = 'avatar-iniciales';
        span.textContent = '+';
        contenedor.appendChild(span);
    }
};

if (modalEditar) {
    const formulario = document.getElementById('form-editar-categoria');
    const vista = document.getElementById('editar-categoria-vista');
    const archivo = document.getElementById('editar-categoria-imagen');
    const quitar = document.getElementById('editar-categoria-quitar');

    document.querySelectorAll('[data-editar-categoria]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const datos = JSON.parse(boton.dataset.editarCategoria);

            formulario.action = datos.accion;
            document.getElementById('editar-categoria-accion').value = datos.accion;
            document.getElementById('titulo-editar-categoria').textContent = `Editar categoría · ${datos.nombre}`;
            document.getElementById('editar-categoria-nombre').value = datos.nombre;
            document.getElementById('editar-categoria-descripcion').value = datos.descripcion ?? '';

            const esImplemento = datos.tipo === 'implemento';
            const stock = document.getElementById('editar-categoria-stock');
            document.getElementById('editar-categoria-fila-stock').hidden = !esImplemento;
            stock.required = esImplemento;
            stock.value = datos.stock_minimo ?? 0;

            archivo.value = '';
            vistaFoto(vista, datos.imagen);
            quitar.hidden = !datos.imagen;
            quitar.querySelector('input').checked = false;

            formulario.querySelectorAll('.invalid-feedback').forEach((e) => e.remove());
            modalEditar.showModal();
        });
    });

    archivo.addEventListener('change', () => {
        const elegido = archivo.files?.[0];
        if (elegido && elegido.type.startsWith('image/')) {
            vistaFoto(vista, URL.createObjectURL(elegido));
        }
    });

    // Si hubo errores de validación, el pop-up se vuelve a abrir con lo escrito.
    if (modalEditar.querySelector('.invalid-feedback') && formulario.action) {
        modalEditar.showModal();
    }
}

document.querySelectorAll('#modal-unidades-categoria [data-cerrar], #modal-editar-categoria [data-cerrar]').forEach((boton) => {
    boton.addEventListener('click', () => boton.closest('dialog')?.close());
});
