<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Planes|Suscripciones</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css', 'resources/js/planes.js'])
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <script src="components/panel_izq.js"></script>
  <div class="flexbox">

    <x-panel-izq />

    <div class="contenido">
      <x-nav_bar />
      <main class="pagina">
        @if (session('mensaje'))
        <div class="alert alert-success" id="mensaje">
          {{ session('mensaje') }}
        </div>
        @endif

        <div class="pagina-titulo">
          <div>
            <h1>Planes y suscripciones</h1>
            <p>Administración interna de planes. No se realizan compras de autoservicio.</p>
          </div>
          <button class="btn-primario" id="abrir-crear-plan">+ Crear plan</button>
        </div>

        <section class="planes-contenedor">

          <button
            type="button"
            class="flecha-carrusel flecha-planes flecha-planes-anterior"
            id="planes-anterior"
            aria-label="Ver planes anteriores">
            <i class="fas fa-chevron-left"></i>
          </button>

          <div class="planes">
            <div class="planes-track" id="planes-track">

              @forelse ($planes as $plan)

              <article class="tarjeta-plan">

                <div class="plan-cabecera">
                  <h3>{{ $plan->nombre_plan }}</h3>
                </div>

                <div class="plan-precio">
                  <strong>
                    $ {{ number_format($plan->costo, 0, ',', '.') }}
                  </strong>

                  @if ($plan->estado_plan)
                  <span class="badge verde">Activo</span>
                  @else
                  <span class="badge gris">Inactivo</span>
                  @endif
                </div>

                <small>
                  COP · {{ $plan->cantidad_entradas }}
                  {{ $plan->tipo_plan === 'tiquetera' ? 'entradas' : 'días' }}
                </small>

                <p>{{ $plan->descripcion ?? 'Sin descripción' }}</p>

                <div class="plan-acciones">

                  <button
                    type="button"
                    class="btn-borde btn-editar-plan"
                    data-id="{{ $plan->plan_id }}"
                    data-nombre="{{ $plan->nombre_plan }}"
                    data-precio="{{ $plan->costo }}"
                    data-cantidad="{{ $plan->cantidad_entradas }}"
                    data-tipo="{{ $plan->tipo_plan }}"
                    data-descripcion="{{ $plan->descripcion }}"
                    data-estado="{{ $plan->estado_plan ? 'Activo' : 'Inactivo' }}"
                    data-actualizacion="{{ $plan->ultima_actualizacion?->format('d/m/Y h:i a') ?? 'Sin registro' }}"
                    data-suscripciones="{{ $plan->suscripciones_count }}"
                    data-activas="{{ $plan->suscripciones_activas_count }}">
                    Consultar / editar
                  </button>

                  @if ($plan->estado_plan)

                  <form action="{{ route('planes.destroy', $plan->plan_id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-plan-activo">Desactivar</button>
                  </form>

                  @else

                  <form action="{{ route('planes.activar', $plan->plan_id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-plan-activo">Activar</button>
                  </form>

                  @endif

                </div>

              </article>

              @empty

              <p>No hay planes registrados.</p>

              @endforelse

            </div>
          </div>

          <button
            type="button"
            class="flecha-carrusel flecha-planes flecha-planes-siguiente"
            id="planes-siguiente"
            aria-label="Ver siguientes planes">
            <i class="fas fa-chevron-right"></i>
          </button>

        </section>

        <section class="suscripciones">
          <div class="susc-cabecera">
            <div>
              <h2>Suscripciones</h2>
              <small>
                {{ $activas }} activas · {{ $vencidas }} vencidas · {{ $porActivar }} por activar ·
                {{ $usuariosSinSuscripcion }} usuarios sin suscripción
              </small>
            </div>
            <button type="button" class="enlace" id="abrir-asignar-suscripcion">Asignar suscripción →</button>
          </div>

          <form class="filtros" method="GET" action="{{ route('planes.index') }}" id="form-filtros-suscripciones">
            <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar usuario o identificación">
            <select name="estado" data-autoenviar>
              <option value="">Estado: todos</option>
              <option value="activa" @selected(request('estado') === 'activa')>Activa</option>
              <option value="por_activar" @selected(request('estado') === 'por_activar')>Por activar</option>
              <option value="vencida" @selected(request('estado') === 'vencida')>Vencida</option>
            </select>
            <select name="plan" data-autoenviar>
              <option value="">Plan: todos</option>
              @foreach ($planes as $plan)
              <option value="{{ $plan->plan_id }}" @selected((string) request('plan') === (string) $plan->plan_id)>
                {{ $plan->nombre_plan }}
              </option>
              @endforeach
            </select>
            @if (request()->hasAny(['buscar', 'estado', 'plan']))
            <a href="{{ route('planes.index') }}" class="btn-borde">Limpiar</a>
            @endif
          </form>

          <table class="tabla">
            <thead>
              <tr>
                <th>Usuario</th>
                <th>Plan</th>
                <th>Inicio</th>
                <th>Vencimiento</th>
                <th>Entradas / días</th>
                <th>Pago</th>
                <th>Suscripción</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($suscripciones as $suscripcion)
              @php
                $inicio = \Illuminate\Support\Carbon::parse($suscripcion->fecha_inicio);
                $fin = $suscripcion->fecha_fin ? \Illuminate\Support\Carbon::parse($suscripcion->fecha_fin) : null;
                $pagado = (float) $suscripcion->total_pagado;
                $costo = (float) $suscripcion->costo;
              @endphp
              <tr>
                <td>
                  {{ $suscripcion->nombre }} {{ $suscripcion->apellido }}
                  <br><small>{{ $suscripcion->documento }}</small>
                </td>
                <td>{{ $suscripcion->nombre_plan }}</td>
                <td>{{ $inicio->format('d/m/Y') }}</td>
                <td>{{ $fin ? $fin->format('d/m/Y') : 'Sin vencimiento' }}</td>
                <td>
                  @if ($suscripcion->tipo_plan === 'tiquetera')
                  {{ $suscripcion->cantidad_dias_restantes }} entradas
                  @elseif ($fin)
                  {{ max(0, (int) now()->startOfDay()->diffInDays($fin->copy()->startOfDay(), false)) }} días
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
                  @elseif ($suscripcion->estado_calculado === 'por_activar')
                  <span class="badge amarillo">Por activar</span>
                  @else
                  <span class="badge rojo">Vencida</span>
                  @endif
                </td>
                <td>
                  <button
                    type="button"
                    class="btn-borde btn-editar-suscripcion"
                    data-id="{{ $suscripcion->suscripcion_id }}"
                    data-usuario="{{ $suscripcion->nombre }} {{ $suscripcion->apellido }} · {{ $suscripcion->documento }}"
                    data-plan="{{ $suscripcion->plan_id }}"
                    data-inicio="{{ $inicio->format('Y-m-d') }}"
                    data-fin="{{ $fin?->format('Y-m-d') }}"
                    data-entradas="{{ $suscripcion->cantidad_dias_restantes }}">
                    Modificar
                  </button>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="8">
                  {{ request()->hasAny(['buscar', 'estado', 'plan']) ? 'No hay suscripciones que coincidan con los filtros.' : 'No hay suscripciones registradas.' }}
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>

          <div class="paginacion">
            <small>Mostrando {{ $suscripciones->firstItem() ?? 0 }}–{{ $suscripciones->lastItem() ?? 0 }} de {{ $suscripciones->total() }} registros</small>
            <div>
              @if ($suscripciones->onFirstPage())
              <button class="btn-pag" disabled>Anterior</button>
              @else
              <a href="{{ $suscripciones->previousPageUrl() }}" class="btn-pag">Anterior</a>
              @endif

              @foreach ($suscripciones->getUrlRange(max(1, $suscripciones->currentPage() - 2), min($suscripciones->lastPage(), $suscripciones->currentPage() + 2)) as $pagina => $url)
              <a href="{{ $url }}" class="btn-pag {{ $pagina == $suscripciones->currentPage() ? 'actual' : '' }}">{{ $pagina }}</a>
              @endforeach

              @if ($suscripciones->hasMorePages())
              <a href="{{ $suscripciones->nextPageUrl() }}" class="btn-pag">Siguiente</a>
              @else
              <button class="btn-pag" disabled>Siguiente</button>
              @endif
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>


  <!-- ===== VENTANA: CREAR PLAN ===== -->
  <dialog class="modal-plan" id="modal-crear-plan" aria-labelledby="titulo-crear-plan">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2 id="titulo-crear-plan">Crear plan</h2>
          <p>06 · Administrador · Valores en pesos colombianos</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form action="{{ route('planes.store') }}" method="POST" id="form-crear-plan">
        @csrf

        <div class="fila-plan una">
          <label class="campo-plan">
            <span>Nombre del plan *</span>
            <input
              type="text"
              name="nombre_plan"
              maxlength="30"
              placeholder="Ej. Semestral universitario"
              value="{{ old('nombre_plan') }}"
              class="@error('nombre_plan') is-invalid @enderror"
              required>

            @error('nombre_plan')
            <div class="invalid-feedback d-block">
              {{ $message }}
            </div>
            @enderror
          </label>
        </div>

        <div class="fila-plan">
          <label class="campo-plan">
            <span>Tipo de plan *</span>
            <select name="tipo_plan" id="tipo-plan-crear" required>
              <option value="tiquetera">Tiquetera</option>
              <option value="suscripcion">Suscripción</option>
            </select>
          </label>

          <label class="campo-plan">
            <span id="etiqueta-cantidad-crear">Cantidad de entradas *</span>
            <input
              type="number"
              name="cantidad_entradas"
              min="1"
              step="1"
              placeholder="Ej. 10"
              required>
          </label>
        </div>

        <div class="fila-plan una">
          <label class="campo-plan">
            <span>Precio COP *</span>
            <input
              type="number"
              name="costo"
              min="0"
              step="0.01"
              placeholder="165000"
              required>
          </label>
        </div>

        <div class="fila-plan una">
          <label class="campo-plan">
            <span>Descripción</span>
            <input
              type="text"
              name="descripcion"
              placeholder="Describe las características del plan.">
          </label>
        </div>

        <div class="acciones-modal">
          <button type="button" class="btn-borde" id="cancelar-crear-plan" data-cerrar>
            Cancelar
          </button>

          <button type="submit" class="btn-primario">
            Guardar plan
          </button>
        </div>
      </form>
    </div>
  </dialog>
  <!-- ===== VENTANA: CONSULTAR / EDITAR PLAN (HU-34, HU-36) ===== -->
  @php($erroresPlan = $errors->editarPlan)
  <dialog class="modal-plan" id="modal-editar-plan" aria-labelledby="titulo-editar-plan">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2 id="titulo-editar-plan">Consultar / editar plan</h2>
          <p id="editar-plan-sub">Detalle del plan seleccionado</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      {{-- HU-36: detalles del plan --}}
      <dl class="detalle-plan" id="detalle-plan">
        <div><dt>Estado</dt><dd data-detalle="estado">—</dd></div>
        <div><dt>Suscripciones activas</dt><dd data-detalle="activas">—</dd></div>
        <div><dt>Suscripciones totales</dt><dd data-detalle="suscripciones">—</dd></div>
        <div><dt>Última actualización</dt><dd data-detalle="actualizacion">—</dd></div>
      </dl>

      <form
        id="form-editar-plan"
        method="POST"
        action="{{ old('plan_id') ? route('planes.update', old('plan_id')) : '' }}"
        data-accion-base="{{ url('/planes') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="plan_id" id="editar-plan-id" value="{{ old('plan_id') }}">

        <div class="fila-plan una">
          <label class="campo-plan"><span>Nombre del plan *</span>
            <input type="text" name="nombre_plan" id="editar-nombre-plan" maxlength="30" value="{{ old('nombre_plan') }}" required>
          </label>
        </div>

        <div class="fila-plan">
          <label class="campo-plan"><span>Tipo de plan *</span>
            <select name="tipo_plan" id="editar-tipo-plan" required>
              <option value="tiquetera" @selected(old('tipo_plan') === 'tiquetera')>Tiquetera</option>
              <option value="suscripcion" @selected(old('tipo_plan') === 'suscripcion')>Suscripción</option>
            </select>
          </label>
          <label class="campo-plan"><span id="etiqueta-cantidad-editar">Cantidad de entradas *</span>
            <input type="number" name="cantidad_entradas" id="editar-cantidad-plan" min="1" step="1" value="{{ old('cantidad_entradas') }}" required>
          </label>
        </div>

        <div class="fila-plan una">
          <label class="campo-plan"><span>Precio COP *</span>
            <input type="number" name="costo" id="editar-costo-plan" min="0" step="0.01" value="{{ old('costo') }}" required>
          </label>
        </div>

        <div class="fila-plan una">
          <label class="campo-plan"><span>Descripción</span>
            <input type="text" name="descripcion" id="editar-descripcion-plan" maxlength="500" value="{{ old('descripcion') }}">
          </label>
        </div>

        @if ($erroresPlan->any())
        <div class="invalid-feedback d-block">
          @foreach ($erroresPlan->all() as $error)
          <div>{{ $error }}</div>
          @endforeach
        </div>
        @endif

        <div class="aviso" id="aviso-desactivar">
          <span class="aviso-icono">ⓘ</span>
          <div>
            <strong>Modificar el plan no altera las suscripciones existentes</strong>
            <p>El nuevo precio y la nueva cantidad aplican a las suscripciones que se asignen desde ahora. Para habilitar o deshabilitar el plan usa el botón de la tarjeta.</p>
          </div>
        </div>

        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
      </form>
    </div>
  </dialog>

  <!-- ===== VENTANA: ASIGNAR SUSCRIPCIÓN ===== -->
  @php($erroresAsignar = $errors->asignar)
  <dialog class="modal-plan" id="modal-asignar-suscripcion" aria-labelledby="titulo-asignar-suscripcion">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2 id="titulo-asignar-suscripcion">Asignar suscripción</h2>
          <p>La suscripción se activa cuando el pago del plan esté verificado.</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form action="{{ route('suscripciones.store') }}" method="POST" id="form-asignar-suscripcion">
        @csrf
        <div class="fila-plan una">
          <label class="campo-plan"><span>Documento del usuario *</span>
            <input type="text" name="documento" inputmode="numeric" pattern="[0-9]{8,11}" maxlength="11" value="{{ old('documento') }}" placeholder="Ej. 1001234567" required>
          </label>
        </div>

        <div class="fila-plan una">
          <label class="campo-plan"><span>Plan *</span>
            <select name="plan_id" id="asignar-plan" required>
              <option value="">Selecciona un plan</option>
              @foreach ($planesActivos as $plan)
              <option value="{{ $plan->plan_id }}" data-tipo="{{ $plan->tipo_plan }}" data-cantidad="{{ $plan->cantidad_entradas }}" @selected((string) old('plan_id') === (string) $plan->plan_id)>
                {{ $plan->nombre_plan }} · ${{ number_format($plan->costo, 0, ',', '.') }} · {{ $plan->cantidad_entradas }} {{ $plan->tipo_plan === 'tiquetera' ? 'entradas' : 'días' }}
              </option>
              @endforeach
            </select>
          </label>
        </div>

        <div class="fila-plan">
          <label class="campo-plan"><span>Fecha de inicio *</span>
            <input type="date" name="fecha_inicio" min="{{ date('Y-m-d') }}" value="{{ old('fecha_inicio', date('Y-m-d')) }}" required>
          </label>
          <label class="campo-plan" id="campo-asignar-fin"><span>Vencimiento (opcional, tiqueteras)</span>
            <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}">
          </label>
        </div>

        <p class="nota-plan">En los planes de suscripción el vencimiento se calcula con los días de vigencia del plan.</p>

        @if ($erroresAsignar->any())
        <div class="invalid-feedback d-block">
          @foreach ($erroresAsignar->all() as $error)
          <div>{{ $error }}</div>
          @endforeach
        </div>
        @endif

        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Asignar</button>
        </div>
      </form>
    </div>
  </dialog>

  <!-- ===== VENTANA: MODIFICAR SUSCRIPCIÓN (HU-37, HU-39) ===== -->
  @php($erroresSuscripcion = $errors->editarSuscripcion)
  <dialog class="modal-plan" id="modal-editar-suscripcion" aria-labelledby="titulo-editar-suscripcion">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2 id="titulo-editar-suscripcion">Modificar suscripción</h2>
          <p id="editar-suscripcion-usuario">{{ old('usuario_texto') }}</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form
        id="form-editar-suscripcion"
        method="POST"
        action="{{ old('suscripcion_id') ? route('suscripciones.update', old('suscripcion_id')) : '' }}"
        data-accion-base="{{ url('/suscripciones') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="suscripcion_id" id="editar-suscripcion-id" value="{{ old('suscripcion_id') }}">
        <input type="hidden" name="usuario_texto" id="editar-suscripcion-texto" value="{{ old('usuario_texto') }}">
        <input type="hidden" id="editar-suscripcion-inicio" value="{{ old('fecha_inicio_ref') }}" name="fecha_inicio_ref">
        @foreach (['buscar', 'estado', 'plan', 'page'] as $filtro)
        @if (request()->filled($filtro))
        <input type="hidden" name="{{ $filtro }}" value="{{ request($filtro) }}">
        @endif
        @endforeach

        <div class="fila-plan una">
          <label class="campo-plan"><span>Plan asociado *</span>
            <select name="plan_id" id="editar-suscripcion-plan" required>
              @foreach ($planes as $plan)
              <option value="{{ $plan->plan_id }}" data-tipo="{{ $plan->tipo_plan }}" data-cantidad="{{ $plan->cantidad_entradas }}" data-activo="{{ $plan->estado_plan ? 1 : 0 }}" @selected((string) old('plan_id') === (string) $plan->plan_id) @disabled(!$plan->estado_plan)>
                {{ $plan->nombre_plan }} · ${{ number_format($plan->costo, 0, ',', '.') }}{{ $plan->estado_plan ? '' : ' (inactivo)' }}
              </option>
              @endforeach
            </select>
          </label>
        </div>

        <div class="fila-plan">
          <label class="campo-plan"><span>Fecha de vencimiento</span>
            <input type="date" name="fecha_fin" id="editar-suscripcion-fin" value="{{ old('fecha_fin') }}">
          </label>
          <label class="campo-plan"><span id="etiqueta-entradas-suscripcion">Entradas restantes *</span>
            <input type="number" name="cantidad_dias_restantes" id="editar-suscripcion-entradas" min="0" step="1" value="{{ old('cantidad_dias_restantes') }}" required>
          </label>
        </div>

        <p class="nota-plan">Al cambiar de plan se proponen el vencimiento y las entradas del nuevo plan; puedes ajustarlos antes de guardar. Si el nuevo plan cuesta más de lo pagado, la suscripción vuelve a quedar por activar.</p>

        @if ($erroresSuscripcion->any())
        <div class="invalid-feedback d-block">
          @foreach ($erroresSuscripcion->all() as $error)
          <div>{{ $error }}</div>
          @endforeach
        </div>
        @endif

        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
      </form>
    </div>
  </dialog>

  @if ($errors->has('nombre_plan') || $errors->has('cantidad_entradas') || $errors->has('costo') || $errors->has('tipo_plan'))
  <script>
    document.addEventListener('DOMContentLoaded', () => document.getElementById('modal-crear-plan')?.showModal());
  </script>
  @endif

  @if ($erroresPlan->any())
  <script>
    document.addEventListener('DOMContentLoaded', () => document.getElementById('modal-editar-plan')?.showModal());
  </script>
  @endif

  @if ($erroresAsignar->any())
  <script>
    document.addEventListener('DOMContentLoaded', () => document.getElementById('modal-asignar-suscripcion')?.showModal());
  </script>
  @endif

  @if ($erroresSuscripcion->any())
  <script>
    document.addEventListener('DOMContentLoaded', () => document.getElementById('modal-editar-suscripcion')?.showModal());
  </script>
  @endif
</body>

</html>
