<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGIP | Reservas de espacios</title>
  @vite(['resources/css/styles.css', 'resources/js/app.js', 'resources/css/panel_izq.css', 'resources/css/nav_bar.css', 'resources/js/espacios.js'])
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
            <h1>Reservas de espacios</h1>
            <p>Consulta la disponibilidad por horario y registra reservas para usuarios o instructores. Horario: {{ $horaApertura }} a {{ $horaCierre }}.</p>
          </div>
          <button class="btn-primario" id="abrir-registrar-reserva">+ Registrar reserva</button>
        </div>

        @if (session('success'))
        <p class="mensaje-exito">{{ session('success') }}</p>
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

        {{-- HU-48: disponibilidad según el horario --}}
        <section class="tarjeta">
          <h2>Disponibilidad</h2>

          <form class="filtros" method="GET" action="{{ route('reservas.index') }}">
            <label>Fecha
              <input type="date" name="fecha" value="{{ $fecha->format('Y-m-d') }}">
            </label>
            <label>Desde
              <input type="time" name="hora_inicio" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ $horaInicio }}">
            </label>
            <label>Hasta
              <input type="time" name="hora_fin" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ $horaFin }}">
            </label>
            <select name="espacio">
              <option value="">Espacio: todos</option>
              @foreach ($todosLosEspacios as $item)
              <option value="{{ $item->espacio_id }}" @selected((string) request('espacio') === (string) $item->espacio_id)>{{ $item->nombre_espacio }}</option>
              @endforeach
            </select>
            <button type="submit" class="btn-borde">Consultar</button>
          </form>

          <p class="nota-plan">
            {{ $fecha->format('d/m/Y') }}
            @if ($rangoConsultado)
            · espacios libres entre {{ $rangoConsultado[0]->format('h:i a') }} y {{ $rangoConsultado[1]->format('h:i a') }} marcados en verde.
            @else
            · indica un rango de horas para ver qué espacios están libres.
            @endif
          </p>

          <div class="grilla-espacios">
            @forelse ($espacios as $espacio)
            <article class="tarjeta-espacio {{ $espacio->libre_en_rango === true ? 'libre' : ($espacio->libre_en_rango === false ? 'ocupado' : '') }}">
              <h3>{{ $espacio->nombre_espacio }}</h3>
              <span class="badge {{ $espacio->habilitado ? ($espacio->nombre_estado === 'Reservado' ? 'amarillo' : 'verde') : 'rojo' }}">
                {{ $espacio->nombre_estado }}
              </span>
              @if ($espacio->capacidad)
              <small>· {{ $espacio->capacidad }} personas</small>
              @endif

              @if ($rangoConsultado)
              <p><strong>{{ $espacio->libre_en_rango ? 'Libre en el horario consultado' : 'No disponible en el horario consultado' }}</strong></p>
              @endif

              @if ($espacio->reservas->isEmpty())
              <ul><li>Sin reservas este día.</li></ul>
              @else
              <ul>
                @foreach ($espacio->reservas as $reserva)
                <li>
                  {{ \Illuminate\Support\Carbon::parse($reserva->fecha_inicio)->format('h:i a') }} –
                  {{ \Illuminate\Support\Carbon::parse($reserva->fecha_fin)->format('h:i a') }}
                  · {{ $reserva->nombre_destinatario }}
                </li>
                @endforeach
              </ul>
              @endif

              @if ($espacio->habilitado)
              <button type="button" class="btn-borde btn-reservar-espacio" data-espacio="{{ $espacio->espacio_id }}">Reservar</button>
              @endif
            </article>
            @empty
            <p>No hay espacios registrados.</p>
            @endforelse
          </div>
        </section>

        {{-- Listado de reservas --}}
        <section class="tarjeta">
          <h2>Reservas</h2>

          <form class="filtros" method="GET" action="{{ route('reservas.index') }}">
            <input type="hidden" name="fecha" value="{{ $fecha->format('Y-m-d') }}">
            <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por documento, nombre o espacio...">
            <select name="estado_reserva" data-autoenviar>
              <option value="">Próximas (todas activas y futuras)</option>
              @foreach ($estadosReserva as $estado)
              <option value="{{ $estado->estado_reserva_id }}" @selected((string) request('estado_reserva') === (string) $estado->estado_reserva_id)>{{ $estado->nombre_estado }}</option>
              @endforeach
            </select>
            <button type="submit" class="btn-borde">Buscar</button>
          </form>

          <table class="tabla">
            <thead>
              <tr>
                <th>Espacio</th>
                <th>Reservado para</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($reservas as $reserva)
              @php
                $inicio = \Illuminate\Support\Carbon::parse($reserva->fecha_inicio);
                $fin = \Illuminate\Support\Carbon::parse($reserva->fecha_fin);
              @endphp
              <tr>
                <td>{{ $reserva->nombre_espacio }}</td>
                <td>
                  {{ $reserva->nombre_destinatario }}
                  <br><small>{{ $reserva->tipo_destinatario }} · {{ $reserva->documento_destinatario }}</small>
                </td>
                <td>{{ $inicio->format('d/m/Y') }}</td>
                <td>{{ $inicio->format('h:i a') }} – {{ $fin->format('h:i a') }}</td>
                <td>
                  <span class="badge {{ match ($reserva->nombre_estado) { 'Confirmada', 'En curso' => 'verde', 'Pendiente' => 'amarillo', 'Cancelada' => 'rojo', default => 'gris' } }}">
                    {{ $reserva->nombre_estado }}
                  </span>
                </td>
                <td>
                  @if (in_array($reserva->nombre_estado, ['Pendiente', 'Confirmada'], true) && $fin->isFuture())
                  <form method="POST" action="{{ route('reservas.cancelar', $reserva->reserva_id) }}" class="form-cancelar-reserva">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-borde">Cancelar</button>
                  </form>
                  @else
                  —
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6">No hay reservas para mostrar.</td>
              </tr>
              @endforelse
            </tbody>
          </table>

          <div class="paginacion">
            <small>Mostrando {{ $reservas->firstItem() ?? 0 }}–{{ $reservas->lastItem() ?? 0 }} de {{ $reservas->total() }} reservas</small>
            <div>
              @if ($reservas->onFirstPage())
              <button class="btn-pag" disabled>Anterior</button>
              @else
              <a href="{{ $reservas->previousPageUrl() }}" class="btn-pag">Anterior</a>
              @endif
              @if ($reservas->hasMorePages())
              <a href="{{ $reservas->nextPageUrl() }}" class="btn-pag">Siguiente</a>
              @else
              <button class="btn-pag" disabled>Siguiente</button>
              @endif
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- ===== VENTANA: REGISTRAR RESERVA (HU-49) ===== -->
  @php($erroresReserva = $errors->reservar)
  <dialog class="modal-plan" id="modal-registrar-reserva">
    <div class="modal-plan-cuerpo">
      <div class="modal-plan-cab">
        <div>
          <h2>Registrar reserva</h2>
          <p>El espacio debe estar habilitado y libre en el horario indicado.</p>
        </div>
        <button type="button" class="modal-plan-cerrar" data-cerrar aria-label="Cerrar">✕</button>
      </div>

      <form method="POST" action="{{ route('reservas.store') }}">
        @csrf
        <div class="fila-plan">
          <label class="campo-plan"><span>Reservar para *</span>
            <select name="tipo_destinatario" required>
              <option value="usuario" @selected(old('tipo_destinatario', 'usuario') === 'usuario')>Usuario</option>
              <option value="instructor" @selected(old('tipo_destinatario') === 'instructor')>Instructor</option>
            </select>
          </label>
          <label class="campo-plan"><span>Documento *</span>
            <input type="text" name="documento" maxlength="12" inputmode="numeric" value="{{ old('documento') }}" required>
          </label>
        </div>

        <div class="fila-plan una">
          <label class="campo-plan"><span>Espacio *</span>
            <select name="espacio_id" id="reserva-espacio" required>
              <option value="">Selecciona un espacio</option>
              @foreach ($espaciosReservables as $espacio)
              <option value="{{ $espacio->espacio_id }}" @selected((string) old('espacio_id') === (string) $espacio->espacio_id)>{{ $espacio->nombre_espacio }}</option>
              @endforeach
            </select>
          </label>
        </div>

        <div class="fila-plan">
          <label class="campo-plan"><span>Fecha *</span>
            <input type="date" name="fecha" min="{{ date('Y-m-d') }}" value="{{ old('fecha', $fecha->format('Y-m-d')) }}" required>
          </label>
          <label class="campo-plan"><span>Desde *</span>
            <input type="time" name="hora_inicio" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_inicio', $horaInicio) }}" required>
          </label>
          <label class="campo-plan"><span>Hasta *</span>
            <input type="time" name="hora_fin" min="{{ $horaApertura }}" max="{{ $horaCierre }}" value="{{ old('hora_fin', $horaFin) }}" required>
          </label>
        </div>

        @if ($erroresReserva->any())
        <div class="mensaje-error">
          <ul>
            @foreach ($erroresReserva->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <div class="modal-plan-acciones">
          <button type="button" class="btn-borde" data-cerrar>Cancelar</button>
          <button type="submit" class="btn-primario">Guardar reserva</button>
        </div>
      </form>
    </div>
  </dialog>

  @if ($errors->reservar->any())
  <script>
    document.addEventListener('DOMContentLoaded', () => document.getElementById('modal-registrar-reserva')?.showModal());
  </script>
  @endif
</body>

</html>
