@extends('layouts.sigip')

@section('titulo', 'Inicio')
@section('descripcion', $esAdmin ? 'Resumen general del gimnasio para la administración.' : 'Resumen de la operación de hoy.')

@section('contenido')
@vite(['resources/css/inicio.css', 'resources/js/inicio.js', 'resources/js/asistente.js'])

<section class="inicio-bienvenida">
  <div>
    <p class="inicio-saludo">{{ $hoy->hour < 12 ? 'Buenos días' : ($hoy->hour < 19 ? 'Buenas tardes' : 'Buenas noches') }}, {{ $nombre }}</p>
    <p class="inicio-fecha">{{ ucfirst($hoy->locale('es')->translatedFormat('l j \d\e F \d\e Y')) }} · {{ $rol }}</p>
  </div>
  <p class="inicio-mensaje">
    @if ($esAdmin)
      Aquí tienes cómo va el gimnasio: usuarios, ingresos, uso y planes más elegidos.
    @else
      Aquí tienes lo que pasa hoy en el gimnasio: quién está dentro, lo pendiente y lo programado.
    @endif
  </p>
</section>

{{-- Indicadores principales --}}
<section class="inicio-indicadores" aria-label="Indicadores">
  @foreach ($indicadores as [$titulo, $valor, $detalle])
  <article class="inicio-indicador">
    <span class="inicio-indicador-titulo">{{ $titulo }}</span>
    <span class="inicio-indicador-valor">{{ $valor }}</span>
    <span class="inicio-indicador-detalle">{{ $detalle }}</span>
  </article>
  @endforeach
</section>

{{-- Asistente con IA local (Ollama): responde con los datos del gimnasio, en texto o con gráfica. --}}
<section class="tarjeta asistente" id="asistente" data-url="{{ route('asistente.preguntar') }}" data-estado="{{ route('asistente.estado') }}">
  <div class="asistente-cabecera">
    <div class="asistente-titulo">
      <span class="asistente-icono" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 4.6L18 9l-4.2 1.4L12 15l-1.8-4.6L6 9l4.2-1.4z"/><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg>
      </span>
      <div>
        <h2>Asistente SIGIP</h2>
        <p class="asistente-estado" id="asistente-estado" role="status">Comprobando el asistente…</p>
      </div>
    </div>

    <div class="asistente-formato" role="radiogroup" aria-label="Formato de la respuesta">
      <span>Responder con:</span>
      <label><input type="radio" name="asistente-formato" value="auto" checked> Automático</label>
      <label><input type="radio" name="asistente-formato" value="texto"> Texto</label>
      <label><input type="radio" name="asistente-formato" value="grafica"> Gráfica</label>
    </div>
  </div>

  <div class="asistente-mensajes" id="asistente-mensajes" aria-live="polite">
    <div class="asistente-burbuja asistente-bot">Hola, {{ $nombre }}. Pregúntame por los datos del gimnasio: accesos, suscripciones, planes, préstamos, inventario{{ $esAdmin ? ' o ingresos' : '' }}. Puedo responder en texto o con una gráfica.</div>
  </div>

  <div class="asistente-sugerencias" id="asistente-sugerencias">
    <button type="button" class="asistente-sugerencia">¿Cuántas personas hay dentro ahora?</button>
    <button type="button" class="asistente-sugerencia">Muéstrame los accesos de los últimos 14 días</button>
    <button type="button" class="asistente-sugerencia">¿Cuál es el plan más asignado?</button>
    @if ($esAdmin)
    <button type="button" class="asistente-sugerencia">¿Cómo van los ingresos este semestre?</button>
    @else
    <button type="button" class="asistente-sugerencia">¿Qué implementos están prestados?</button>
    @endif
  </div>

  <form class="asistente-entrada" id="asistente-formulario">
    <label for="asistente-pregunta" class="visualmente-oculto">Escribe tu pregunta</label>
    <input type="text" id="asistente-pregunta" maxlength="500" autocomplete="off" placeholder="Escribe tu pregunta sobre el gimnasio…" required>
    <button type="submit" class="btn-primario" id="asistente-enviar">Enviar</button>
  </form>
  <p class="asistente-aviso">Funciona con un modelo de IA local (Ollama): los datos no salen de SIGIP. Puede equivocarse; verifica las cifras importantes en su módulo.</p>
</section>

{{-- Gráficas: los datos viajan en data-grafica y se dibujan con resources/js/inicio.js --}}
<section class="inicio-graficas {{ count($graficas) === 3 ? 'inicio-graficas-tres' : '' }}">
  @foreach ($graficas as $grafica)
  <article class="tarjeta inicio-grafica {{ !empty($grafica['ancho']) ? 'inicio-grafica-ancha' : '' }}">
    <h2>{{ $grafica['titulo'] }}</h2>
    <span class="tarjeta-sub">{{ $grafica['subtitulo'] }}</span>
    <div class="inicio-lienzo">
      <canvas id="grafica-{{ $grafica['id'] }}" data-grafica="{{ json_encode($grafica) }}" role="img"
        aria-label="{{ $grafica['titulo'] }}. {{ $grafica['subtitulo'] }}"></canvas>
    </div>
    <details class="inicio-tabla">
      <summary>Ver datos en tabla</summary>
      <table class="tabla">
        @if (isset($grafica['datos']['series']))
          <thead><tr><th></th>@foreach ($grafica['datos']['series'] as $serie)<th>{{ $serie['nombre'] }}</th>@endforeach</tr></thead>
          <tbody>
          @foreach ($grafica['datos']['etiquetas'] as $i => $etiqueta)
            <tr><td>{{ $etiqueta }}</td>@foreach ($grafica['datos']['series'] as $serie)<td>{{ $serie['valores'][$i] }}</td>@endforeach</tr>
          @endforeach
          </tbody>
        @else
          <thead><tr><th></th><th>{{ $grafica['serie'] }}</th></tr></thead>
          <tbody>
          @forelse ($grafica['datos']['etiquetas'] as $i => $etiqueta)
            <tr><td>{{ $etiqueta }}</td><td>{{ !empty($grafica['moneda']) ? '$' . number_format($grafica['datos']['valores'][$i], 0, ',', '.') : $grafica['datos']['valores'][$i] }}</td></tr>
          @empty
            <tr><td colspan="2">Sin datos todavía.</td></tr>
          @endforelse
          </tbody>
        @endif
      </table>
    </details>
  </article>
  @endforeach
</section>

{{-- Operador: lo programado para hoy --}}
@if ($listas)
<section class="inicio-graficas">
  @foreach ($listas as $lista)
  <article class="tarjeta">
    <h2>{{ $lista['titulo'] }}</h2>
    <table class="tabla inicio-lista">
      <tbody>
      @forelse ($lista['filas'] as $fila)
        <tr>@foreach ($fila as $valor)<td>{{ $valor }}</td>@endforeach</tr>
      @empty
        <tr><td class="inicio-vacio">{{ $lista['vacio'] }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </article>
  @endforeach
</section>
@endif
@endsection
