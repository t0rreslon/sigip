
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

formulario.addEventListener("submit", (event) => {
    event.preventDefault();

    // Aquí debes enviar los datos al backend
    // y guardar el usuario en la base de datos.

    alert("Formulario validado. Falta conectar el guardado.");

    formulario.reset();
    modal.close();
});
