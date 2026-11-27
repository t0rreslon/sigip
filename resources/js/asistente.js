/**
 * Chat del asistente SIGIP (Ollama) en la página de inicio.
 * - Envía la pregunta, el formato preferido (automático, texto o gráfica) y los últimos turnos.
 * - Si la respuesta trae una gráfica, la dibuja con los mismos estilos de las gráficas del inicio.
 */
import { crear } from './inicio.js';

const panel = document.getElementById('asistente');

if (panel) {
    const mensajes = document.getElementById('asistente-mensajes');
    const formulario = document.getElementById('asistente-formulario');
    const entrada = document.getElementById('asistente-pregunta');
    const enviar = document.getElementById('asistente-enviar');
    const estado = document.getElementById('asistente-estado');
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const historial = [];
    let ocupado = false;

    const formato = () => panel.querySelector('input[name="asistente-formato"]:checked')?.value ?? 'auto';

    const desplazar = () => {
        mensajes.scrollTop = mensajes.scrollHeight;
    };

    const burbuja = (clase, texto) => {
        const div = document.createElement('div');
        div.className = `asistente-burbuja ${clase}`;
        div.textContent = texto;
        mensajes.appendChild(div);
        desplazar();
        return div;
    };

    const dibujarGrafica = (contenedor, grafica) => {
        const tarjeta = document.createElement('figure');
        tarjeta.className = 'asistente-grafica';

        const titulo = document.createElement('figcaption');
        titulo.textContent = grafica.titulo;

        const lienzoCaja = document.createElement('div');
        lienzoCaja.className = 'inicio-lienzo';

        const lienzo = document.createElement('canvas');
        lienzo.setAttribute('role', 'img');
        lienzo.setAttribute('aria-label', grafica.titulo);
        lienzo.dataset.grafica = JSON.stringify(grafica);
        lienzo.dataset.creada = '1';

        lienzoCaja.appendChild(lienzo);
        tarjeta.append(titulo, lienzoCaja);
        contenedor.appendChild(tarjeta);
        crear(lienzo);
        desplazar();
    };

    const comprobarEstado = async () => {
        try {
            const respuesta = await fetch(panel.dataset.estado, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const datos = await respuesta.json();

            estado.textContent = datos.disponible
                ? `En línea · modelo local ${datos.modelo}`
                : `No disponible: ${datos.motivo}`;
            estado.classList.toggle('disponible', !!datos.disponible);
            estado.classList.toggle('no-disponible', !datos.disponible);
        } catch {
            estado.textContent = 'No se pudo comprobar el asistente.';
            estado.classList.add('no-disponible');
        }
    };

    const preguntar = async (texto) => {
        if (ocupado || !texto.trim()) {
            return;
        }

        ocupado = true;
        enviar.disabled = true;
        entrada.value = '';
        burbuja('asistente-usuario', texto);
        const pensando = burbuja('asistente-bot asistente-pensando', 'Pensando…');

        try {
            const respuesta = await fetch(panel.dataset.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ mensaje: texto, formato: formato(), historial: historial.slice(-12) }),
            });

            const datos = await respuesta.json().catch(() => ({}));

            if (!respuesta.ok) {
                const error = respuesta.status === 429
                    ? 'Hiciste muchas preguntas seguidas. Espera un minuto e inténtalo de nuevo.'
                    : (datos.error || datos.message || 'No pude responder en este momento.');
                pensando.textContent = error;
                pensando.classList.replace('asistente-pensando', 'asistente-error');
                return;
            }

            pensando.classList.remove('asistente-pensando');
            pensando.textContent = datos.texto;

            if (datos.grafica) {
                dibujarGrafica(pensando, datos.grafica);
            }

            historial.push({ rol: 'usuario', texto }, { rol: 'asistente', texto: datos.texto });
        } catch {
            pensando.textContent = 'No hay conexión con el servidor. Revisa tu conexión e inténtalo de nuevo.';
            pensando.classList.replace('asistente-pensando', 'asistente-error');
        } finally {
            ocupado = false;
            enviar.disabled = false;
            entrada.focus();
        }
    };

    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        preguntar(entrada.value);
    });

    panel.querySelectorAll('.asistente-sugerencia').forEach((boton) => {
        boton.addEventListener('click', () => preguntar(boton.textContent.trim()));
    });

    comprobarEstado();
}
