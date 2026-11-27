{{--
  Pop-up con las asignaciones de un tipo de plan (tiqueteras o suscripciones):
  búsqueda, filtro por estado y por plan, tabla con editar y ver historial, y paginación propia.
  Parámetros: $id, $titulo, $vista, $prefijo, $registros, $planesTipo, $resumen, $unidad.
--}}
<dialog class="modal-plan modal-registros" id="{{ $id }}" aria-labelledby="titulo-{{ $id }}">
  <div class="modal-plan-cuerpo">
    <div class="modal-plan-cab">
      <div>
        <h2 id="titulo-{{ $id }}">{{ $titulo }}</h2>
        <p>{{ $resumen['activa'] }} activas · {{ $resumen['por_activar'] }} por activar · {{ $resumen['vencida'] }} vencidas</p>
      </div>
      <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
    </div>

    <form class="filtros" method="GET" action="{{ route('planes.index') }}">
      {{-- Al filtrar, el pop-up se vuelve a abrir; se conservan los filtros del otro pop-up. --}}
      <input type="hidden" name="ver" value="{{ $vista }}">
      @foreach (request()->except(['ver', $prefijo . 'buscar', $prefijo . 'estado', $prefijo . 'plan', $prefijo . 'pagina']) as $nombre => $valor)
      @if (is_scalar($valor))
      <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
      @endif
      @endforeach
      <input type="search" name="{{ $prefijo }}buscar" value="{{ request($prefijo . 'buscar') }}" placeholder="Buscar usuario, identificación o plan" aria-label="Buscar">
      <select name="{{ $prefijo }}estado" data-autoenviar aria-label="Estado">
        <option value="">Estado: todos</option>
        <option value="activa" @selected(request($prefijo . 'estado') === 'activa')>Activa</option>
        <option value="por_activar" @selected(request($prefijo . 'estado') === 'por_activar')>Por activar</option>
        <option value="vencida" @selected(request($prefijo . 'estado') === 'vencida')>Vencida</option>
      </select>
      <select name="{{ $prefijo }}plan" data-autoenviar aria-label="{{ $unidad === 'entradas' ? 'Tiquetera' : 'Suscripción' }}">
        <option value="">{{ $unidad === 'entradas' ? 'Tiquetera' : 'Suscripción' }}: todas</option>
        @foreach ($planesTipo as $plan)
        <option value="{{ $plan->plan_id }}" @selected((string) request($prefijo . 'plan') === (string) $plan->plan_id)>{{ $plan->nombre_plan }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn-borde">Buscar</button>
      @if (request()->hasAny([$prefijo . 'buscar', $prefijo . 'estado', $prefijo . 'plan']))
      <a href="{{ route('planes.index', ['ver' => $vista]) }}" class="btn-borde">Limpiar</a>
      @endif
    </form>

    <div class="tabla-desplazable">
      <table class="tabla">
        <thead>
          <tr>
            <th>Usuario</th>
            <th>{{ $unidad === 'entradas' ? 'Tiquetera' : 'Suscripción' }}</th>
            <th>Inicio</th>
            <th>{{ $unidad === 'entradas' ? 'Fin' : 'Vencimiento' }}</th>
            <th>{{ $unidad === 'entradas' ? 'Entradas restantes' : 'Días restantes' }}</th>
            <th>Pago</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($registros as $suscripcion)
          @php
            $inicio = \Illuminate\Support\Carbon::parse($suscripcion->fecha_inicio);
            $fin = $suscripcion->fecha_fin ? \Illuminate\Support\Carbon::parse($suscripcion->fecha_fin) : null;
            $pagado = (float) $suscripcion->total_pagado;
            $costo = (float) $suscripcion->costo;
            $porActivar = $suscripcion->estado_calculado === 'por_activar';
          @endphp
          <tr>
            <td>
              {{ $suscripcion->nombre }} {{ $suscripcion->apellido }}
              <br><small>{{ $suscripcion->documento }}</small>
            </td>
            <td>{{ $suscripcion->nombre_plan }}</td>
            <td>
              @if ($porActivar)
              <span class="texto-suave">Al completar el pago</span>
              @else
              {{ $inicio->format('d/m/Y') }}
              @endif
            </td>
            <td>
              @if ($fin)
              {{ $fin->format('d/m/Y') }}
              @elseif ($porActivar)
              <span class="texto-suave">Se calcula al activarse</span>
              @elseif ($unidad === 'entradas' && (int) $suscripcion->cantidad_dias_restantes <= 0)
              <span class="texto-suave">Entradas agotadas</span>
              @else
              <span class="texto-suave">{{ $unidad === 'entradas' ? 'Al usar la última entrada' : 'Sin vencimiento' }}</span>
              @endif
            </td>
            <td>
              @if ($unidad === 'entradas')
              {{ $suscripcion->cantidad_dias_restantes }} de {{ $suscripcion->cantidad_entradas }}
              @elseif ($fin)
              {{ max(0, (int) now()->startOfDay()->diffInDays($fin->copy()->startOfDay(), false)) }} días
              @else
              {{ $suscripcion->cantidad_entradas }} días (al activarse)
              @endif
            </td>
            <td>
              @if ($pagado >= $costo)
              <span class="badge verde">Verificado</span>
              @elseif ($pagado > 0)
              <span class="badge amarillo">Parcial · ${{ number_format($pagado, 0, ',', '.') }}</span>
              @else
              <span class="badge amarillo">Pendiente</span>
              @endif
            </td>
            <td>
              @if ($suscripcion->estado_calculado === 'activa')
              <span class="badge verde">Activa</span>
              @elseif ($porActivar)
              <span class="badge amarillo">Por activar</span>
              @else
              <span class="badge rojo">Vencida</span>
              @endif
            </td>
            <td class="acciones-registro">
              <button
                type="button"
                class="btn-borde btn-editar-suscripcion"
                data-id="{{ $suscripcion->suscripcion_id }}"
                data-usuario="{{ $suscripcion->nombre }} {{ $suscripcion->apellido }} · {{ $suscripcion->documento }}"
                data-plan="{{ $suscripcion->plan_id }}"
                data-inicio="{{ $inicio->format('Y-m-d') }}"
                data-fin="{{ $fin?->format('Y-m-d') }}"
                data-entradas="{{ $suscripcion->cantidad_dias_restantes }}"
                data-activa="{{ $suscripcion->estado ? 1 : 0 }}">
                Editar
              </button>
              <a href="{{ route('historial.registro', ['suscripcion', $suscripcion->suscripcion_id]) }}" class="enlace-historial">Ver historial</a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8">
              {{ request()->hasAny([$prefijo . 'buscar', $prefijo . 'estado', $prefijo . 'plan']) ? 'No hay registros que coincidan con los filtros.' : 'No hay ' . ($unidad === 'entradas' ? 'tiqueteras' : 'suscripciones') . ' asignadas.' }}
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="paginacion">
      <small>Mostrando {{ $registros->firstItem() ?? 0 }}–{{ $registros->lastItem() ?? 0 }} de {{ $registros->total() }} registros</small>
      <div>
        @php $enlace = fn ($url) => $url ? $url . (str_contains($url, '?') ? '&' : '?') . 'ver=' . $vista : null; @endphp
        @if ($registros->onFirstPage())
        <button class="btn-pag" disabled>Anterior</button>
        @else
        <a href="{{ $enlace($registros->previousPageUrl()) }}" class="btn-pag">Anterior</a>
        @endif

        @foreach ($registros->getUrlRange(max(1, $registros->currentPage() - 2), min($registros->lastPage(), $registros->currentPage() + 2)) as $pagina => $url)
        <a href="{{ $enlace($url) }}" class="btn-pag {{ $pagina == $registros->currentPage() ? 'actual' : '' }}">{{ $pagina }}</a>
        @endforeach

        @if ($registros->hasMorePages())
        <a href="{{ $enlace($registros->nextPageUrl()) }}" class="btn-pag">Siguiente</a>
        @else
        <button class="btn-pag" disabled>Siguiente</button>
        @endif
      </div>
    </div>
  </div>
</dialog>
