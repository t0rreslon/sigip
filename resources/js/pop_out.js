const modal = document.getElementById("modalRegistro");
const formulario = document.getElementById("formRegistro");

document.getElementById("abrir-dialogo").addEventListener("click", () => {
    modal.showModal();
});

document.getElementById("btnCerrar").addEventListener("click", () => {
    modal.close();
});

document.getElementById("btnCancelar").addEventListener("click", () => {
    modal.close();
});