<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <title>Sistema de informacion | SIGIP</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css'])
</head>

<body>
    <div class="flexbox">
        <div class="panel-izq_index">
            <div class="logo">
                <img src="imagenes/Logoo.png" alt="logo" class="logo-imagen">

            </div>
            <div class="logo-texto">
                <h3>Institución Universitaria</h3>
                <h3>Pascual Bravo</h3>
            </div>

            <div class="informacion">
               

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
                <img src="imagenes/Logoo.png"
                    alt="Gimnasio universitario">
            </div>

            <footer class="footer">
                Bienestar Universitario · Gestión integral del gimnasio
            </footer>



        </div>



        <div class="panel-dere">

            <div class="formulario-login">

                <span class="etiqueta-login">NUEVA CONTRASEÑA</span>
                <h1>Crea una nueva contraseña</h1>
                <p class="subtitulo">Mínimo 10 caracteres con mayúsculas, minúsculas, números y un símbolo. El enlace solo se puede usar una vez.</p>

                <form action="{{ route('password.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cuenta" value="{{ $cuenta }}">
                    <input type="hidden" name="token" value="{{ $token }}">

                    @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                        @endforeach
                    </div>
                    @endif

                    <div class="campo">
                        <label for="password">Nueva contraseña *</label>
                        <input type="password" id="password" name="password" minlength="10" autocomplete="new-password" required>
                    </div>

                    <div class="campo">
                        <label for="password_confirmation">Confirmar contraseña *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" minlength="10" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="boton-login"><span>➜</span> Guardar contraseña</button>
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