@php
    $cuenta = auth()->user();
    $esAdmin = $cuenta?->tieneTipo('Administrador');
    $esPersonal = $cuenta?->tieneTipo('Administrador', 'Operador');
    $esInstructor = $cuenta?->tieneTipo('Instructor');

    // Opciones del menú según el tipo de cuenta: [ruta, ícono, texto, visible].
    $opciones = [
        ['inicio', 'https://img.icons8.com/material-outlined/24/home--v2.png', 'Inicio', $esPersonal],
        ['usuarios', 'https://img.icons8.com/pastel-glyph/64/conference-call.png', 'Usuarios', $esPersonal],
        ['planes.index', 'https://img.icons8.com/windows/32/verified-account.png', 'Planes y suscripciones', $esPersonal],
        ['pago.index', 'https://img.icons8.com/external-anggara-glyph-anggara-putra/32/external-scan-user-interface-anggara-basic-outline-anggara-putra-2.png', 'Pagos', $esPersonal],
        ['control-acceso.index', 'https://img.icons8.com/fluency-systems-regular/24/door-opened.png', 'Control de acceso', $esPersonal],
        ['reservas.index', 'https://img.icons8.com/material-outlined/24/tear-off-calendar.png', 'Reservas de espacios', $esPersonal],
        ['espacios.index', 'https://img.icons8.com/windows/32/room.png', 'Espacios', $esPersonal],
        ['prestamos', 'https://img.icons8.com/pulsar-line/48/handshake.png', 'Préstamos y devoluciones', $esPersonal],
        ['inventario.index', 'https://img.icons8.com/windows/32/dumbbell.png', 'Inventario y mantenimiento', $esPersonal],
        ['actividades.index', 'https://img.icons8.com/fluency-systems-regular/24/activity.png', 'Actividades', $esPersonal],
        ['instructores.index', 'https://img.icons8.com/external-anggara-basic-outline-anggara-putra/24/external-contact-user-interface-anggara-basic-outline-anggara-putra.png', 'Instructores y turnos', $esAdmin],
        ['operadores.index', 'https://img.icons8.com/ios/50/admin-settings-male.png', 'Operadores', $esAdmin],
        ['actividades.reporte', 'https://img.icons8.com/pastel-glyph/64/bar-chart--v2.png', 'Reportes', $esPersonal],
        ['portal.horario', 'https://img.icons8.com/material-outlined/24/tear-off-calendar.png', 'Mi horario', $esInstructor],
        ['portal.actividades', 'https://img.icons8.com/fluency-systems-regular/24/activity.png', 'Mis actividades', $esInstructor],
        ['portal.reservas', 'https://img.icons8.com/windows/32/room.png', 'Mis reservas', $esInstructor],
        ['perfil', 'https://img.icons8.com/pastel-glyph/64/conference-call.png', 'Mi perfil', (bool) $cuenta],
    ];
@endphp
<aside class="panel-izq">
    <div class="encabezado">
        <div align=center>
            <img src="{{ asset('imagenes/Logoo.png') }}" alt="logo" style="width: 80px; height: auto;">
            <p>
                Gimnasio universitario<br>
                Pascual Bravo, Medellín
            </p>
        </div>
        <br>
    </div>

    <nav class="opciones" aria-label="Navegación principal">
        <div class="d-grid gap-2 mx-auto">
            @foreach ($opciones as [$ruta, $icono, $texto, $visible])
            @if ($visible)
            <a href="{{ route($ruta) }}" class="button-options {{ request()->routeIs(\Illuminate\Support\Str::before($ruta, '.') . '*') ? 'activa' : '' }}">
                <img src="{{ $icono }}" alt="">
                <span>{{ $texto }}</span>
            </a>
            @endif
            @endforeach
        </div>
    </nav>

    <footer class="footer">
        Bienestar Universitario · Gestión integral del gimnasio
    </footer>
</aside>
