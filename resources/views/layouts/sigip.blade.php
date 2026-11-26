<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>SIGIP | @yield('titulo')</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css', 'resources/js/sigip.js'])
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>

<body>
  <div class="flexbox">
    <x-panel-izq />
    <div class="contenido">
      <x-nav_bar />
      <main class="pagina">
        <div class="pagina-titulo">
          <div>
            <h1>@yield('titulo')</h1>
            @hasSection('descripcion')
            <p>@yield('descripcion')</p>
            @endif
          </div>
          <div class="acciones-titulo">
            @yield('acciones')
          </div>
        </div>

        @if (session('success'))
        <p class="mensaje-exito">{{ session('success') }}</p>
        @endif

        @if (session('error'))
        <p class="mensaje-error">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
        <div class="mensaje-error">
          <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        @yield('contenido')
      </main>
    </div>
  </div>

  @stack('modales')

  {{-- Si hubo errores de validación, se reabre el modal que envió el formulario. --}}
  @if ($errors->any() && old('_modal'))
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => document.getElementById(@json(old('_modal')))?.showModal());
  </script>
  @endif
</body>

</html>
