@extends('layouts.sigip')

@section('titulo', 'Mi perfil')
@section('descripcion', 'Consulta y actualiza tus datos personales y tu contraseña.')

@section('contenido')
<section class="tarjeta">
  <h2>Cuenta</h2>
  <dl class="detalle-grid">
    <div><dt>Usuario</dt><dd>{{ $cuenta->login }}</dd></div>
    <div><dt>Tipo de cuenta</dt><dd>{{ $cuenta->nombreTipo() }}</dd></div>
    <div><dt>Documento</dt><dd>{{ $personal->documento ?? '—' }}</dd></div>
    <div><dt>Cuenta creada</dt><dd>{{ \Illuminate\Support\Carbon::parse($cuenta->fecha_creacion)->format('d/m/Y') }}</dd></div>
  </dl>
</section>

<section class="tarjeta">
  <h2>Datos personales</h2>
  <form method="POST" action="{{ route('perfil.update') }}">
    @csrf
    @method('PUT')
    <div class="fila-plan">
      <label class="campo-plan"><span>Nombre *</span><input type="text" name="nombre" maxlength="50" value="{{ old('nombre', $personal->nombre ?? '') }}" required></label>
      <label class="campo-plan"><span>Apellido *</span><input type="text" name="apellido" maxlength="50" value="{{ old('apellido', $personal->apellido ?? '') }}" required></label>
    </div>
    <div class="fila-plan">
      <label class="campo-plan"><span>Teléfono</span><input type="tel" name="telefono" pattern="[0-9]{7,11}" maxlength="11" value="{{ old('telefono', $personal->telefono ?? '') }}"></label>
      <label class="campo-plan"><span>Correo (para recuperar la contraseña)</span><input type="email" name="correo" maxlength="80" value="{{ old('correo', $personal->correo ?? '') }}"></label>
    </div>
    <div class="modal-plan-acciones">
      <button type="submit" class="btn-primario">Guardar datos</button>
    </div>
  </form>
</section>

<section class="tarjeta">
  <h2>Cambiar contraseña</h2>
  <form method="POST" action="{{ route('perfil.clave') }}">
    @csrf
    @method('PUT')
    <label class="campo-plan"><span>Contraseña actual *</span><input type="password" name="clave_actual" autocomplete="current-password" required></label>
    <div class="fila-plan">
      <label class="campo-plan"><span>Nueva contraseña *</span><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
      <label class="campo-plan"><span>Confirmar *</span><input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
    </div>
    <p class="nota-plan">Mínimo 8 caracteres con letras y números.</p>
    <div class="modal-plan-acciones">
      <button type="submit" class="btn-primario">Cambiar contraseña</button>
    </div>
  </form>
</section>
@endsection
