@extends('layouts.sigip')

@section('titulo', 'Inicio')
@section('descripcion', $esAdmin ? 'Resumen general del gimnasio para la administración.' : 'Resumen de la operación de hoy.')

@section('contenido')
@vite(['resources/css/inicio.css', 'resources/js/inicio.js'])

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

{{-- Gráficas: los datos viajan en data-grafica y se dibujan con resources/js/inicio.js --}}
<section class="inicio-graficas">
  @foreach ($graficas as $grafica)
  <article class="tarjeta inicio-grafica">
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
