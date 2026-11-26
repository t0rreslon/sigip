<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <title>Verificar código | SIGIP</title>
    @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css'])
</head>

<body>
    <div class="flexbox">
        <div class="panel-izq_index">
            <div class="logo">
                <img src="{{ asset('imagenes/Logoo.png') }}" alt="Logo Institución Universitaria Pascual Bravo" class="logo-imagen">
            </div>
            <div class="logo-texto">
                <h3>Institución Universitaria</h3>
                <h3>Pascual Bravo</h3>
            </div>
            <div class="informacion">
                <h2>Sistema de Información para la Gestión Integral del Gimnasio Universitario</h2>
            </div>
            <footer class="footer">
                Bienestar Universitario · Gestión integral del gimnasio
            </footer>
        </div>

        <div class="panel-dere">
            <div class="formulario-login">
                <span class="etiqueta-login">VERIFICACIÓN EN DOS PASOS</span>
                <h1>Ingresa tu código</h1>
                <p class="subtitulo">Abre la app de autenticación de tu celular y escribe el código de 6 dígitos de SIGIP. El código cambia cada 30 segundos.</p>

                <form action="{{ route('doble-factor.verificar') }}" method="POST">
                    @csrf

                    @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                        @endforeach
                    </div>
                    @endif

                    <div class="campo">
                        <label for="codigo">Código de verificación *</label>
                        <input type="text" id="codigo" name="codigo" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7"
                            autocomplete="one-time-code" placeholder="000000" autofocus required>
                    </div>

                    <button type="submit" class="boton-login"><span>➜</span> Verificar</button>

                    <div class="recuperar">
                        <a href="{{ route('login') }}">Volver a iniciar sesión</a>
                    </div>
                </form>

                <div class="soporte">
                    <p>¿Perdiste el celular? Pide al soporte técnico de SIGIP que desactive tu doble factor.</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
