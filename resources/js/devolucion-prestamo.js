
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalDevolucion');
    const botonesDevolucion = document.querySelectorAll('.registrar-devolucion');

    const form = document.getElementById('formDevolucion');
    const listaDevoluciones = document.getElementById('listaDevoluciones');

    const infoDestinatario = document.getElementById('infoDestinatario');
    const fechaPrestamo = document.getElementById('fechaPrestamo');

    // Elementos de la barra de progreso
    const progresoTexto = document.getElementById('progresoTexto');
    const progresoDetalle = document.getElementById('progresoDetalle');
    const barraProgreso = document.getElementById('barraProgreso');
    const progresoLlenado = document.getElementById('progresoLlenado');

    const btnCerrar = document.getElementById('btnCerrarDev');
    const btnCancelar = document.getElementById('btnCancelarDev');
    const btnConfirmar = form?.querySelector('button[type="submit"]');

    // Abrir el modal y cargar los implementos pendientes
    botonesDevolucion.forEach(boton => {
        boton.addEventListener('click', () => {
            if (!modal || !form || !listaDevoluciones) return;

            let implementos = [];

            // Obtener los implementos desde el atributo data-implementos
            try {
                implementos = JSON.parse(boton.dataset.implementos || '[]');
            } catch (error) {
                console.error('Error al cargar los implementos:', error);
                return;
            }

            // Asignar la ruta de devolución del préstamo seleccionado
            form.action = boton.dataset.url;

            // Mostrar la información del préstamo seleccionado
            if (infoDestinatario) {
                infoDestinatario.textContent =
                    `${boton.dataset.destinatario} · ${boton.dataset.tipo}`;
            }

            if (fechaPrestamo) {
                fechaPrestamo.textContent = boton.dataset.fecha;
            }

            // Calcular el progreso de devolución
            const total = Number(boton.dataset.cantidad);
            const pendientes = implementos.length;
            const devueltos = Math.max(0, total - pendientes);

            const porcentaje = total > 0
                ? Math.round((devueltos / total) * 100)
                : 0;

            // Actualizar los números
            if (progresoTexto) {
                progresoTexto.textContent = `${devueltos} / ${total}`;
            }

            if (progresoDetalle) {
                progresoDetalle.textContent =
                    `${devueltos} de ${total} implementos devueltos`;
            }

            // Actualizar el llenado de la barra
            if (progresoLlenado) {
                progresoLlenado.style.width = `${porcentaje}%`;
            }

            // Actualizar los atributos de accesibilidad
            if (barraProgreso) {
                barraProgreso.setAttribute('aria-valuemax', total);
                barraProgreso.setAttribute('aria-valuenow', devueltos);
            }

            // Limpiar el contenido anterior del modal
            listaDevoluciones.innerHTML = '';

            // Verificar si existen implementos pendientes
            if (implementos.length === 0) {
                const mensaje = document.createElement('p');
                mensaje.textContent = 'Este préstamo no tiene implementos pendientes por devolver.';

                listaDevoluciones.appendChild(mensaje);

                if (btnConfirmar) {
                    btnConfirmar.disabled = true;
                }
            } else {
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                }

                // Crear un selector para cada implemento pendiente
                implementos.forEach(implemento => {
                    const contenedor = document.createElement('div');
                    contenedor.className = 'col-12';

                    const etiqueta = document.createElement('label');
                    etiqueta.className = 'form-label';
                    etiqueta.textContent = implemento.nombre_implemento;

                    if (implemento.nombre_categoria) {
                        etiqueta.textContent += ` - ${implemento.nombre_categoria}`;
                    }

                    const select = document.createElement('select');
                    select.className = 'form-select';
                    select.name = `devoluciones[${implemento.prestamo_implemento_id}]`;
                    select.required = false;

                    // Opción inicial
                    const opcionInicial = document.createElement('option');
                    opcionInicial.value = '';
                    opcionInicial.textContent = 'Seleccionar estado de devolución';
                    opcionInicial.disabled = true;
                    opcionInicial.selected = true;

                    select.appendChild(opcionInicial);

                    // Estados permitidos para la devolución
                    const estados = [
                        { valor: 'Intacto', texto: 'Intacto - Disponible' },
                        { valor: 'Averiado', texto: 'Averiado - Requiere revisión' },
                        { valor: 'Perdido', texto: 'Perdido' }
                    ];

                    estados.forEach(estado => {
                        const opcion = document.createElement('option');
                        opcion.value = estado.valor;
                        opcion.textContent = estado.texto;

                        select.appendChild(opcion);
                    });

                    contenedor.appendChild(etiqueta);
                    contenedor.appendChild(select);

                    listaDevoluciones.appendChild(contenedor);
                });
            }

            // Mostrar el modal
            modal.showModal();
        });
    });

    // Cerrar el modal desde el botón superior
    if (btnCerrar) {
        btnCerrar.addEventListener('click', () => {
            modal?.close();
        });
    }

    // Cerrar el modal desde el botón Cancelar
    if (btnCancelar) {
        btnCancelar.addEventListener('click', () => {
            modal?.close();
        });
    }

    // Permitir enviar únicamente los implementos seleccionados para devolución
    if (form) {
        form.addEventListener('submit', (event) => {
            const selectores = listaDevoluciones.querySelectorAll('select[name^="devoluciones["]');

            const seleccionados = Array.from(selectores).filter(select => select.value !== '');

            // Evitar enviar el formulario si no se seleccionó ningún implemento
            if (seleccionados.length === 0) {
                event.preventDefault();
                alert('Selecciona al menos un implemento para registrar su devolución.');
                return;
            }

            // Excluir del envío los implementos que todavía no se van a devolver
            selectores.forEach(select => {
                if (select.value === '') {
                    select.disabled = true;
                }
            });
        });
    }
});
