const identificacion = document.getElementById('identificacion');

const consultar = document.getElementById('consultar-identificacion');

const registrarIngreso = document.getElementById('registrar-ingreso');

const registrarSalida = document.getElementById('registrar-salida');

// CONSULTAR USUARIO
consultar.addEventListener('click', async () => {

    const valor = identificacion.value.trim();

    if (!valor) {
        alert('Ingresa una identificación.');
        return;
    }

    const respuesta = await fetch(
        `/control-acceso/consultar?identificacion=${encodeURIComponent(valor)}`
    );

    const datos = await respuesta.json();

    if (!respuesta.ok) {
        alert(datos.mensaje || 'No fue posible consultar el usuario.');
        return;
    }

    document.getElementById('nombre-usuario').textContent = datos.usuario.nombre;

    document.getElementById('identificacion-usuario').textContent =
        datos.usuario.identificacion;

    document.getElementById('estado-usuario').textContent =
        datos.usuario.estado;

    document.getElementById('correo-usuario').textContent =
        datos.usuario.correo;

    document.getElementById('titulo-estado').textContent =
        datos.puedeIngresar ? 'Acceso autorizado' : 'Acceso denegado';

    document.getElementById('descripcion-estado').textContent =
        datos.motivo;

    // Guardar el ID para las siguientes operaciones.
    registrarIngreso.dataset.usuarioId = datos.usuario.usuario_id;
    registrarSalida.dataset.usuarioId = datos.usuario.usuario_id;

});


// REGISTRAR INGRESO
registrarIngreso.addEventListener('click', async () => {

    const usuarioId = registrarIngreso.dataset.usuarioId;

    if (!usuarioId) {
        alert('Primero debes consultar un usuario.');
        return;
    }

    const respuesta = await fetch('/control-acceso/ingreso', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            usuario_id: usuarioId
        })
    });

    const datos = await respuesta.json();

    alert(datos.mensaje);

});


// REGISTRAR SALIDA
registrarSalida.addEventListener('click', async () => {

    const usuarioId = registrarSalida.dataset.usuarioId;

    if (!usuarioId) {
        alert('Primero debes consultar un usuario.');
        return;
    }

    const respuesta = await fetch('/control-acceso/salida', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            usuario_id: usuarioId
        })
    });

    const datos = await respuesta.json();

    alert(datos.mensaje);

});