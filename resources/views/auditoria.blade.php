@extends('layouts.sigip')

@section('titulo', 'Auditoría del sistema')
@section('descripcion', 'Quién hizo qué y cuándo: cambios en los registros y acciones de todas las cuentas.')

@section('contenido')
@php
  $integra = collect($integridad)->every(fn ($t) => $t['alterados'] === 0);
  $nombresTabla = ['auditoria_tablas' => 'cambios en registros', 'auditoria_movimientos' => 'acciones de las cuentas'];
@endphp

<section class="tarjeta">
  <div class="resumen">
    @if ($integra)
    <span class="chip verde">✓ Auditoría íntegra: ningún registro fue alterado ni borrado</span>
    @else
    <span class="chip rojo">⚠ Se detectaron registros de auditoría alterados o borrados</span>
    @endif
    @foreach ($integridad as $tabla => $r)
    <span class="resumen-texto">
      {{ number_format($r['total'], 0, ',', '.') }} {{ $nombresTabla[$tabla] }}
      @if ($r['alterados'])
      · {{ $r['alterados'] }} alterado(s) desde el #{{ $r['primer_id'] }}
      @endif
    </span>
    @endforeach
  </div>
  <p class="resumen-texto" style="margin:0">
    Los registros de auditoría no se pueden modificar ni eliminar: la base de datos lo impide y cada registro
    queda encadenado al anterior con una huella SHA-256, que se verifica al abrir esta página y todos los días a las 2:00 a. m.
  </p>
</section>

<section class="tarjeta">
  <div class="pestanas">
    <a href="{{ route('auditoria', ['vista' => 'cambios', 'desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')]) }}" class="{{ $vista === 'cambios' ? 'activa' : '' }}">Cambios en registros ({{ number_format($resumen['cambios'], 0, ',', '.') }})</a>
    <a href="{{ route('auditoria', ['vista' => 'acciones', 'desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')]) }}" class="{{ $vista === 'acciones' ? 'activa' : '' }}">Acciones de las cuentas ({{ number_format($resumen['acciones'], 0, ',', '.') }})</a>
  </div>

  @if ($resumen['fallidos'])
  <div class="resumen">
    <a class="chip amarillo" href="{{ route('auditoria', ['vista' => 'acciones', 'desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d'), 'accion' => $idLoginFallido]) }}">
      {{ $resumen['fallidos'] }} intento(s) de ingreso fallidos o bloqueos en el periodo
    </a>
  </div>
  @endif

  @if ($vista === 'cambios')
  <form class="filtros" method="GET" action="{{ route('auditoria') }}">
    <input type="hidden" name="vista" value="cambios">
    <label>Desde <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" data-autoenviar></label>
    <label>Hasta <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" data-autoenviar></label>
    <select name="modulo" aria-label="Módulo" data-autoenviar>
      <option value="">Módulo: todos</option>
      @foreach (\App\Support\Auditoria::MODULOS as $tabla => $nombre)
      <option value="{{ $tabla }}" @selected(request('modulo') === $tabla)>{{ $nombre }}</option>
      @endforeach
    </select>
    <select name="operacion" aria-label="Tipo de cambio" data-autoenviar>
      <option value="">Cambio: todos</option>
      @foreach (\App\Support\Auditoria::OPERACIONES as $clave => $nombre)
      <option value="{{ $clave }}" @selected(request('operacion') === $clave)>{{ $nombre }}</option>
      @endforeach
    </select>
    <select name="usuario" aria-label="Realizado por" data-autoenviar>
      <option value="">Realizado por: todos</option>
      @foreach ($usuarios as $login)
      <option value="{{ $login }}" @selected(request('usuario') === $login)>{{ $login }}</option>
      @endforeach
    </select>
    <input type="search" name="registro" value="{{ request('registro') }}" placeholder="N.º de registro" aria-label="Número de registro" style="width:130px">
    <button type="submit" class="btn-borde">Filtrar</button>
  </form>

  <table class="tabla">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Módulo</th>
        <th>Registro</th>
        <th>Cambio</th>
        <th>Realizado por</th>
        <th>Detalle</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($registros as $registro)
      <tr>
        <td>{{ \Illuminate\Support\Carbon::parse($registro->creado_en)->format('d/m/Y h:i a') }}</td>
        <td>{{ $registro->modulo }}</td>
        <td>
          @if ($registro->enlace)
          <a href="{{ $registro->enlace }}">#{{ $registro->registro_id }}</a>
          @else
          #{{ $registro->registro_id }}
          @endif
        </td>
        <td><span class="badge {{ match ($registro->operacion) { 'INSERT' => 'verde', 'ESTADO', 'DEVOLUCION' => 'amarillo', 'DELETE' => 'rojo', default => 'morado' } }}">{{ \App\Support\Auditoria::OPERACIONES[$registro->operacion] ?? $registro->operacion }}</span></td>
        <td>{{ $registro->usuario }}</td>
        <td><x-detalle-cambio :registro="$registro" /></td>
      </tr>
      @empty
      <tr><td colspan="6">No hay cambios registrados para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$registros" etiqueta="cambios" />
  @else
  <form class="filtros" method="GET" action="{{ route('auditoria') }}">
    <input type="hidden" name="vista" value="acciones">
    <label>Desde <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" data-autoenviar></label>
    <label>Hasta <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" data-autoenviar></label>
    <select name="tipo_cuenta" aria-label="Tipo de cuenta" data-autoenviar>
      <option value="">Tipo de cuenta: todos</option>
      @foreach ($tiposCuenta as $tipo)
      <option value="{{ $tipo->tipo_cuenta_id }}" @selected((string) request('tipo_cuenta') === (string) $tipo->tipo_cuenta_id)>{{ $tipo->nombre_cuenta }}</option>
      @endforeach
    </select>
    <select name="cuenta" aria-label="Cuenta" data-autoenviar>
      <option value="">Cuenta: todas</option>
      @foreach ($cuentas as $cuenta)
      <option value="{{ $cuenta->cuenta_id }}" @selected((string) request('cuenta') === (string) $cuenta->cuenta_id)>{{ $cuenta->login }}</option>
      @endforeach
    </select>
    <select name="accion" aria-label="Acción" data-autoenviar>
      <option value="">Acción: todas</option>
      @foreach ($acciones as $accion)
      <option value="{{ $accion->tipo_accion_id }}" @selected((string) request('accion') === (string) $accion->tipo_accion_id)>{{ $accion->nombre_accion }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-borde">Filtrar</button>
  </form>

  <table class="tabla">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Cuenta</th>
        <th>Acción</th>
        <th>Detalle</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($movimientos as $movimiento)
      <tr>
        <td>{{ \Illuminate\Support\Carbon::parse($movimiento->fecha_hora)->format('d/m/Y h:i:s a') }}</td>
        <td>
          {{ trim("{$movimiento->nombre} {$movimiento->apellido}") ?: '—' }}<br>
          <small>{{ $movimiento->login ?? 'Cuenta eliminada' }}{{ $movimiento->nombre_cuenta ? ' · ' . $movimiento->nombre_cuenta : '' }}</small>
        </td>
        <td><span class="badge {{ in_array($movimiento->nombre_accion, ['Login fallido', 'Bloqueo de cuenta'], true) ? 'rojo' : 'morado' }}">{{ $movimiento->nombre_accion }}</span></td>
        <td>
          @if (!empty($movimiento->detalle['ruta']))
          {{ \App\Support\NombresRutas::describir($movimiento->detalle['ruta']) }}
          @elseif (!empty($movimiento->detalle['reporte']))
          Reporte PDF de {{ $movimiento->detalle['reporte'] }}
          @elseif (!empty($movimiento->detalle['motivo']))
          {{ $movimiento->detalle['motivo'] }}
          @elseif (!empty($movimiento->detalle['detalle']))
          {{ $movimiento->detalle['detalle'] }}
          @else
          —
          @endif
          @if (!empty($movimiento->detalle['parametros']))
          <br><small>Registro #{{ implode(', #', array_map('strval', $movimiento->detalle['parametros'])) }}</small>
          @endif
        </td>
        <td>{{ $movimiento->ip }}</td>
      </tr>
      @empty
      <tr><td colspan="5">No hay acciones registradas para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>

  <x-paginacion :p="$movimientos" etiqueta="acciones" />
  @endif
</section>
@endsection
