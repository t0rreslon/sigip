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
    aria-label="Ver planes anteriores"
>
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
                        >
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
    aria-label="Ver siguientes planes"
>
    <i class="fas fa-chevron-right"></i>
</button>

</section>

        <section class="suscripciones">
          <div class="susc-cabecera">
            <div>
              <h2>Suscripciones</h2>
              <small>96 activas · 18 vencidas · 1 por activar · 13 usuarios sin registro</small>
            </div>
            <a href="#" class="enlace">Asignar suscripción →</a>
          </div>

          <div class="filtros">
            <input type="search" placeholder="Buscar usuario o identificación">
            <select>
              <option>Estado: todos</option>
              <option>Activa</option>
              <option>Por activar</option>
              <option>Vencida</option>
            </select>
            <select>
              <option>Plan: todos</option>
              <option>Mensual universitario</option>
              <option>Trimestral bienestar</option>
            </select>
          </div>

          <table class="tabla">
            <thead>
              <tr>
                <th>Usuario</th>
                <th>Plan</th>
                <th>Inicio</th>
                <th>Vencimiento</th>
                <th>Pago</th>
                <th>Suscripción</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Mariana López</td>
                <td>Mensual universitario</td>
                <td>02 oct 2026</td>
                <td>31 oct 2026</td>
                <td><span class="badge verde">Verificado</span></td>
                <td><span class="badge verde">Activa</span></td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>Diego Ramírez</td>
                <td>Mensual universitario</td>
                <td>22 oct 2026</td>
                <td>20 nov 2026</td>
                <td><span class="badge amarillo">Pendiente</span></td>
                <td><span class="badge amarillo">Por activar</span></td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>Mateo Torres</td>
                <td>Mensual universitario</td>
                <td>16 sep 2026</td>
                <td>15 oct 2026</td>
                <td><span class="badge verde">Verificado</span></td>
                <td><span class="badge rojo">Vencida</span></td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>Sara Restrepo</td>
                <td>Trimestral bienestar</td>
                <td>03 oct 2026</td>
                <td>31 dic 2026</td>
                <td><span class="badge verde">Verificado</span></td>
                <td><span class="badge verde">Activa</span></td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
            </tbody>
          </table>

          <div class="paginacion">
            <small>Mostrando 1–4 de 115 registros</small>
            <div>
              <button class="btn-pag" disabled>Anterior</button>
              <button class="btn-pag actual">1</button>
              <button class="btn-pag">2</button>
              <button class="btn-pag">Siguiente</button>
            </div>
          </div>
        </section>

        <footer class="pie-pagina">06 / 15 · Datos ficticios de demostración · Institución Universitaria Pascal Bravo · Corte: 22 oct 2026, 10:30</footer>
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
                required
            >
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
                required
            >
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
                required
            >
        </label>
    </div>

    <div class="fila-plan una">
        <label class="campo-plan">
            <span>Descripción</span>
            <input
                type="text"
                name="descripcion"
                placeholder="Describe las características del plan."
            >
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
  <dialog class="modal-plan" id="modal-editar-plan" aria-labelledby="titulo-editar-plan">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2 id="titulo-editar-plan">Editar plan</h2>
          <p id="editar-plan-sub">06 · Mensual universitario · Plan existente</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form id="form-editar-plan">
        <input type="hidden" name="indicePlan">
        <div class="fila-plan una">
          <label class="campo-plan"><span>Nombre del plan *</span>
            <input type="text" name="nombre" required></label>
        </div>
        <div class="fila-plan">
          <label class="campo-plan"><span>Precio COP *</span>
            <input type="text" name="precio" type="number" min="1" step="1" inputmode="numeric" required></label>
          <label class="campo-plan"><span>Vigencia en días *</span>
            <input type="number" name="vigencia" min="1" step="1" required></label>
        </div>
        <div class="fila-plan">
          <label class="campo-plan"><span>Entradas *</span>
            <select name="entradas">
              <option>Ilimitadas</option>
              <option>Una entrada</option>
            </select></label>
          <label class="campo-plan"><span>Estado *</span>
            <select name="estado">
              <option>Activo</option>
              <option>Inactivo</option>
            </select></label>
        </div>
        <div class="fila-plan una">
          <label class="campo-plan"><span>Descripción *</span>
            <input type="text" name="descripcion" required></label>
        </div>
        <div class="fila-plan una">
          <label class="campo-plan"><span>Beneficios</span>
            <input type="text" name="beneficios"></label>
        </div>

        <div class="aviso" id="aviso-desactivar">
          <span class="aviso-icono">ⓘ</span>
          <div>
            <strong>Desactivar no elimina suscripciones existentes</strong>
            <p>Impide nuevas asignaciones. Se conserva la vigencia de las ya registradas.</p>
          </div>
        </div>

        <button type="button" class="btn-borde btn-desactivar" id="btn-desactivar-plan">Desactivar plan</button>

        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
      </form>
    </div>
  </dialog>

</body>

</html>