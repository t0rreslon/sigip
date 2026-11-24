@extends('layouts.sigip')

@section('titulo', $titulo)
@section('descripcion', $subtitulo)

@section('acciones')
<a href="{{ $volver }}" class="btn-borde">← Volver</a>
@endsection

@section('contenido')
<section class="tarjeta">
  <x-historial-cambios :registros="$registros" />
</section>
@endsection
