@php
    $cuenta = auth()->user();
    $personal = $cuenta?->personal;
    $nombreCuenta = $personal ? trim($personal->nombre . ' ' . $personal->apellido) : ($cuenta->login ?? 'Invitado');
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
    ];
    $seccionActual = $secciones[request()->segment(1)] ?? 'Inicio';
@endphp
<!-- ABRE NAVBAR -->
<nav class="navbar barra-superior">
    <div class="ruta-navegacion">
        <span>Gimnasio Universitario</span>
        <span>/</span>
        <span>{{ $seccionActual }}</span>
    </div>

    <div class="acciones-superiores">
        <!-- PERFIL DE LA CUENTA -->
        <div class="perfil-administrador">
            <span class="avatar-administrador">{{ $iniciales ?: '?' }}</span>
            <span class="datos-administrador">
                <strong>{{ $nombreCuenta }}</strong>
                <small>{{ $cuenta?->nombreTipo() ?? '' }}</small>
            </span>
        </div>

        @auth
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-cerrar-sesion" title="Cerrar sesión">Salir</button>
        </form>
        @endauth
    </div>
</nav>
