@php
    $cuenta = auth()->user();
    $personal = $cuenta?->personal;
    $nombreCuenta = $personal ? trim($personal->nombre . ' ' . $personal->apellido) : ($cuenta->login ?? 'Invitado');
    $fotoCuenta = \App\Support\Imagenes::url($personal?->imagen, 'personal');
    $iniciales = collect(explode(' ', $nombreCuenta))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
    $secciones = [
        'usuarios' => 'Usuarios',
        'planes' => 'Planes y suscripciones',
        'pago' => 'Pagos',
        'control-acceso' => 'Control de acceso',
        'prestamos' => 'Préstamos y devoluciones',
        'inventario' => 'Inventario',
        'espacios' => 'Espacios',
        'reservas' => 'Reservas de espacios',
        'actividades' => 'Actividades',
        'programaciones' => 'Actividades',
        'instructores' => 'Instructores y turnos',
        'operadores' => 'Operadores',
        'auditoria' => 'Auditoría',
        'historial' => 'Historial de cambios',
        'inicio' => 'Inicio',
        'notificaciones' => 'Notificaciones',
        'perfil' => 'Mi perfil',
        'mi' => 'Mi espacio',
    ];
    $seccionActual = $secciones[request()->segment(1)] ?? 'Inicio';
    // HU-199 / HU-204: solicitudes pendientes para el personal.
    $pendientes = $cuenta && $cuenta->tieneTipo('Administrador', 'Operador') ? \App\Http\Controllers\NotificacionController::pendientes() : 0;
@endphp
<!-- ABRE NAVBAR -->
<nav class="navbar barra-superior">
    <div class="ruta-navegacion">
        <span>Gimnasio Universitario</span>
        <span>/</span>
        <span>{{ $seccionActual }}</span>
    </div>

    <div class="acciones-superiores">
        @if ($cuenta && $cuenta->tieneTipo('Administrador', 'Operador'))
        <a class="btn-notificaciones" href="{{ route('notificaciones') }}" aria-label="Notificaciones: {{ $pendientes }} pendientes" title="Solicitudes pendientes">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                <path d="M10 21h4" />
            </svg>
        </a>
        @if ($pendientes > 0)
        <span class="contador-notificaciones">{{ $pendientes }}</span>
        @endif
        @endif

        <!-- PERFIL DE LA CUENTA (HU-189) -->
        <a class="perfil-administrador" href="{{ route('perfil') }}" title="Mi perfil">
            <span class="avatar-administrador">
                @if ($fotoCuenta)
                <img src="{{ $fotoCuenta }}" alt="">
                @else
                {{ $iniciales ?: '?' }}
                @endif
            </span>
            <span class="datos-administrador">
                <strong>{{ $nombreCuenta }}</strong>
                <small>{{ $cuenta?->nombreTipo() ?? '' }}</small>
            </span>
        </a>

        @auth
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-cerrar-sesion" title="Cerrar sesión">Salir</button>
        </form>
        @endauth
    </div>
</nav>
