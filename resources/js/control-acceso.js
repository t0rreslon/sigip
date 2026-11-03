
const identificacion = document.getElementById('identificacion');
const consultar = document.getElementById('consultar-identificacion');
const registrarIngreso = document.getElementById('registrar-ingreso');
const registrarSalida = document.getElementById('registrar-salida');

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let usuarioSeleccionado = null;

// Elementos de la tarjeta
const nombreUsuario = document.getElementById('nombre-usuario');
const identificacionUsuario = document.getElementById('identificacion-usuario');
const estadoUsuario = document.getElementById('estado-usuario');
const planUsuario = document.getElementById('plan-usuario');
const vigenciaUsuario = document.getElementById('vigencia-usuario');
const pagoUsuario = document.getElementById('pago-usuario');
const correoUsuario = document.getElementById('correo-usuario');
const telefonoUsuario = document.getElementById('telefono-usuario');
const tituloEstado = document.getElementById('titulo-estado');
const descripcionEstado = document.getElementById('descripcion-estado');
const fechaConsulta = document.getElementById('fecha-consulta');

// Formatear fechas
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha de finalización';

    // Las fechas sin hora se interpretan como fechas locales,
    // evitando que cambien de día por la conversión UTC.
    const soloFecha = /^\d{4}-\d{2}-\d{2}$/.test(fecha);

    const fechaFormateada = soloFecha
        ? new Date(`${fecha}T12:00:00`)
        : new Date(fecha);

    return fechaFormateada.toLocaleDateString('es-CO', {
        timeZone: 'America/Bogota',
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

// Consultar usuario
async function consultarUsuario() {
    const valor = identificacion.value.trim();

    if (!valor) {
        alert('Ingresa un nombre, identificación o correo.');
        return;
    }

    try {
        const respuesta = await fetch(
            `/control-acceso/consultar?buscar=${encodeURIComponent(valor)}`,
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const datos = await respuesta.json();

        if (!respuesta.ok || !datos.success) {
            usuarioSeleccionado = null;
            alert(datos.message || 'No fue posible consultar el usuario.');
            return;
        }

        // El controlador devuelve una colección de usuarios.
        // Por ahora mostramos el primer resultado.
        if (datos.usuarios.length !== 1) {
            usuarioSeleccionado = null;
            alert(
                'Se encontraron varios usuarios. ' +
                'Escribe el documento completo o un dato más específico.'
            );
            return;
        }
        const resultado = datos.usuarios[0];

        usuarioSeleccionado = resultado.usuario;

        const usuario = resultado.usuario;
        const suscripciones = resultado.suscripciones;

        // Seleccionar la suscripción más reciente
        const suscripcion = suscripciones.length > 0
            ? suscripciones[0]
            : null;

        mostrarUsuario(usuario, suscripcion);

    } catch (error) {
        console.error('Error al consultar:', error);
        alert('No fue posible conectar con el servidor.');
    }
}

// Mostrar información del usuario
function mostrarUsuario(usuario, suscripcion) {
    nombreUsuario.textContent =
        `${usuario.nombre} ${usuario.apellido}`;

    identificacionUsuario.textContent =
        `Documento: ${usuario.documento}`;

    correoUsuario.textContent = usuario.correo || 'No registrado';
    telefonoUsuario.textContent = usuario.telefono || 'No registrado';

    const usuarioActivo = usuario.estado_usuario === true;

    estadoUsuario.textContent = usuarioActivo ? 'Activo' : 'Inactivo';

    if (!suscripcion) {
        planUsuario.textContent = 'Sin suscripción';
        vigenciaUsuario.textContent = 'No disponible';
        pagoUsuario.textContent = 'Sin pago registrado';

        mostrarEstado(
            false,
            'Acceso denegado',
            'El usuario no tiene una suscripción registrada.'
        );

        return;
    }

    planUsuario.textContent = suscripcion.nombre_plan;

    vigenciaUsuario.textContent =
        `${formatearFecha(suscripcion.fecha_inicio)} - ${
            formatearFecha(suscripcion.fecha_fin)
        }`;

    pagoUsuario.textContent = suscripcion.pago_confirmado
        ? 'Pago verificado'
        : 'Pago pendiente';

    const hoy = new Date();
    const inicio = new Date(suscripcion.fecha_inicio);
    const fin = suscripcion.fecha_fin
        ? new Date(suscripcion.fecha_fin)
        : null;

    const suscripcionActiva = suscripcion.estado === true;

    const fechaValida =
        hoy >= inicio && (!fin || hoy <= fin);

    const tieneEntradas =
        suscripcion.cantidad_dias_restantes > 0;

    let puedeIngresar = true;
    let motivo = 'Usuario activo, suscripción vigente y pago verificado.';

    if (!usuarioActivo) {
        puedeIngresar = false;
        motivo = 'El usuario se encuentra inactivo.';
    } else if (!suscripcionActiva) {
        puedeIngresar = false;
        motivo = 'La suscripción se encuentra inactiva.';
    } else if (!suscripcion.pago_confirmado) {
        puedeIngresar = false;
        motivo = 'El pago de la suscripción no está confirmado.';
    } else if (
        suscripcion.tipo_plan === 'suscripcion' && !fechaValida
    ) {
        puedeIngresar = false;
        motivo = 'La suscripción no está dentro de su periodo de vigencia.';
    } else if (
        suscripcion.tipo_plan === 'tiquetera' && !tieneEntradas
    ) {
        puedeIngresar = false;
        motivo = 'La tiquetera no tiene entradas disponibles.';
    }

    if (suscripcion.tipo_plan === 'tiquetera') {
        pagoUsuario.textContent +=
            ` · Entradas restantes: ${suscripcion.cantidad_dias_restantes}`;
    }

    mostrarEstado(
        puedeIngresar,
        puedeIngresar ? 'Acceso autorizado' : 'Acceso denegado',
        motivo
    );
}

// Actualizar tarjeta de estado
function mostrarEstado(autorizado, titulo, descripcion) {
    tituloEstado.textContent = titulo;
    descripcionEstado.textContent = descripcion;
    registrarSalida.disabled = !usuarioSeleccionado;
    registrarIngreso.disabled = !autorizado;
    registrarIngreso.style.opacity = autorizado ? '1' : '0.5';
    registrarIngreso.style.cursor = autorizado ? 'pointer' : 'not-allowed';
}

// Consultar al hacer clic
consultar.addEventListener('click', consultarUsuario);

// También permitir buscar presionando Enter
identificacion.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        consultarUsuario();
    }
});

// Registrar ingreso
registrarIngreso.addEventListener('click', async () => {
    if (!usuarioSeleccionado) {
        alert('Primero debes consultar un usuario.');
        return;
    }

    try {
        const respuesta = await fetch('/control-acceso/registrar-ingreso', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                usuario_id: usuarioSeleccionado.usuario_id
            })
        });

        const datos = await respuesta.json();

        if (!respuesta.ok || !datos.success) {
            alert(datos.message || 'No fue posible registrar el ingreso.');
            return;
        }

        alert(datos.message);

        // Recargar para actualizar el historial desde PostgreSQL.
        window.location.reload();

    } catch (error) {
        console.error('Error al registrar ingreso:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
});

// Registrar salida
registrarSalida.addEventListener('click', async () => {
    if (!usuarioSeleccionado) {
        alert('Primero debes consultar un usuario.');
        return;
    }

    const confirmar = confirm(
        '¿Deseas registrar la salida de este usuario?'
    );

    if (!confirmar) return;

    try {
        const respuesta = await fetch(
            `/control-acceso/salida/${usuarioSeleccionado.usuario_id}`,
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }
        );

        const datos = await respuesta.json();

        if (!respuesta.ok || !datos.success) {
            alert(datos.message || 'No fue posible registrar la salida.');
            return;
        }

        alert(datos.message);
        window.location.reload();

    } catch (error) {
        console.error('Error al registrar salida:', error);
        alert('Ocurrió un error al conectar con el servidor.');
    }
});

// Mostrar fecha y hora actual de consulta
fechaConsulta.textContent = new Date().toLocaleString('es-CO', {
    timeZone: 'America/Bogota',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
});
