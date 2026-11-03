```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Probar registro de ingreso</title>
</head>
<body>
    <h2>Registrar ingreso de usuario</h2>

    <form action="{{ route('control-acceso.registrar-ingreso') }}" method="POST">
        @csrf

        <label for="usuario_id">ID del usuario:</label>
        <input type="number" name="usuario_id" id="usuario_id" required>

        <button type="submit">Registrar ingreso</button>
    </form>

    @if(session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p>{{ session('error') }}</p>
    @endif
</body>
</html>
```