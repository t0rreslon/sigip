<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('codigo') · @yield('titulo') | SIGIP</title>
    <style>
        :root { --texto: #1f2937; --suave: #4b5563; --fondo: #f5f7fb; --tarjeta: #fff; --acento: #0b4f8a; --borde: #e5e7eb; }
        @media (prefers-color-scheme: dark) {
            :root { --texto: #e5e7eb; --suave: #9ca3af; --fondo: #111827; --tarjeta: #1f2937; --acento: #7cb7ef; --borde: #374151; }
        }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--fondo); color: var(--texto); }
        main { max-width: 480px; margin: 16px; padding: 32px; text-align: center; background: var(--tarjeta); border: 1px solid var(--borde); border-radius: 12px; }
        .codigo { font-size: 3rem; font-weight: 700; color: var(--acento); margin: 0; }
        h1 { font-size: 1.3rem; margin: 8px 0; }
        p { color: var(--suave); line-height: 1.5; }
        a { display: inline-block; margin-top: 12px; padding: 10px 18px; border-radius: 8px; background: var(--acento); color: #fff; text-decoration: none; }
    </style>
</head>

<body>
    <main>
        <p class="codigo">@yield('codigo')</p>
        <h1>@yield('titulo')</h1>
        <p>@yield('mensaje')</p>
        <a href="{{ url('/') }}">Volver al inicio</a>
    </main>
</body>

</html>
