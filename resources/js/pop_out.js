const modal = document.getElementById("modalRegistro");
const formulario = document.getElementById("formRegistro");
const formularioFiltros = document.getElementById("formFiltros");
const campoBuscar = document.getElementById("buscar");

document.getElementById("abrir-dialogo").addEventListener("click", () => {
    modal.showModal();
});

document.getElementById("btnCerrar").addEventListener("click", () => {
    modal.close();
});

document.getElementById("btnCancelar").addEventListener("click", () => {
    modal.close();
});

if (formularioFiltros && campoBuscar) {
    campoBuscar.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            formularioFiltros.requestSubmit();
        }
    });
}


// =====================================================
// 5. OCULTAR MENSAJE DESPUÉS DE 4 SEGUNDOS
// =====================================================

const mensaje = document.getElementById('mensaje');

if (mensaje) {
    setTimeout(() => {
        mensaje.style.display = 'none';
    }, 4000);
}
