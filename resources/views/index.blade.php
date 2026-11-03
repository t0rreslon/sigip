<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <title>Sistema de informacion | SIGIP</title>
  @vite(['resources/js/app.js'])
</head>

<body>
    <div class="flexbox">
        <div class="panel-izq">
            <div class="logo">
                <img src="imagenes/Logoo.png" alt="logo" class="logo-imagen">

            </div>
            <div class="logo-texto">
                <h3>Institución Universitaria</h3>
                <h3>Pascual Bravo</h3>
            </div>

            <div class="informacion">
                <h1>SIGIP</h1>

                <h2>
                    Sistema de Información para la
                    Gestión Integral del Gimnasio
                    Universitario
                </h2>

                <p>
                    Un espacio de gestión para el bienestar
                    de nuestra comunidad universitaria.
                </p>

            </div>
            <div class="imagen-gimnasio">
                <img src=""
                    alt="Gimnasio universitario">
            </div>

            <footer class="footer">
                Bienestar Universitario · Gestión integral del gimnasio
            </footer>



        </div>



        <div class="panel-dere">

            <div class="formulario-login">

                <!-- Etiqueta institucional -->
                <span class="etiqueta-login">
                    ACCESO INSTITUCIONAL
                </span>

                <!-- Título -->
                <h1>Bienvenido a SIGIP</h1>

                <p class="subtitulo">
                    Ingresa con tus credenciales para continuar
                    a tu espacio de trabajo.
                </p>

                <!-- Formulario -->
                <form action="#" method="post">

                    <div class="campo">
                        <label for="correo">
                            Correo institucional o identificación *
                        </label>

                        <input
                            type="text"
                            id="correo"
                            name="correo"
                            placeholder="Ingresa tu correo o identificación"
                            required>
                    </div>

                    <div class="campo">
                        <label for="contrasena">
                            Contraseña *
                        </label>

                        <input
                            type="password"
                            id="contrasena"
                            name="contrasena"
                            placeholder="Ingresa tu contraseña"
                            required>
                    </div>

                    <!-- Recuperar contraseña -->
                    <div class="recuperar">
                        <a href="#">¿Necesitas ayuda para ingresar?</a>
                    </div>

                    <!-- Botón de inicio de sesión -->
                    <button type="submit" class="boton-login">
                        <span>➜</span>
                        Iniciar sesión
                    </button>

                </form>

                <!-- Aviso de seguridad -->
                <div class="aviso-seguridad">


                    <p>
                        Tu rol se identifica automáticamente al autenticarte.
                        No es necesario seleccionarlo.
                    </p>
                </div>

                <!-- Información de soporte -->
                <div class="soporte">
                    <p>Soporte: Bienestar Universitario</p>
                    <p>Uso exclusivo de personal autorizado.</p>
                </div>



            </div>

        </div>



    </div>
    </div>
</body>

</html>