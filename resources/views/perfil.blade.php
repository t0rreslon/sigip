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
      <label class="campo-plan"><span>Nueva contraseña *</span><input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
      <label class="campo-plan"><span>Confirmar *</span><input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
    </div>
    <p class="nota-plan">Mínimo 10 caracteres con mayúsculas, minúsculas, números y un símbolo.</p>
    <div class="modal-plan-acciones">
      <button type="submit" class="btn-primario">Cambiar contraseña</button>
    </div>
  </form>
</section>

@if ($cuenta->tieneTipo('Administrador') || $cuenta->doble_factor_activo)
<section class="tarjeta" id="doble-factor">
  <h2>Verificación en dos pasos</h2>
  @if ($cuenta->doble_factor_activo)
    <p>Estado: <strong>activa</strong>. Al iniciar sesión se pide el código de tu app de autenticación.</p>
    <form method="POST" action="{{ route('perfil.2fa.desactivar') }}" data-confirmar="¿Desactivar la verificación en dos pasos? Tu cuenta quedará protegida solo con la contraseña.">
      @csrf
      @method('DELETE')
      <label class="campo-plan"><span>Contraseña actual *</span><input type="password" name="clave_actual_2fa" autocomplete="current-password" required></label>
      <div class="modal-plan-acciones">
        <button type="submit" class="btn-secundario">Desactivar</button>
      </div>
    </form>
  @elseif ($nuevoSecreto)
    <ol class="nota-plan">
      <li>Instala en tu celular Google Authenticator, Microsoft Authenticator o Authy.</li>
      <li>Elige <em>Ingresar clave de configuración</em> y escribe esta clave (cuenta: <strong>SIGIP:{{ $cuenta->login }}</strong>, basada en tiempo):</li>
    </ol>
    <p class="clave-2fa"><code>{{ \App\Support\Totp::formatear($nuevoSecreto) }}</code></p>
    <form method="POST" action="{{ route('perfil.2fa.activar') }}">
      @csrf
      <label class="campo-plan"><span>Código de 6 dígitos que muestra la app *</span>
        <input type="text" name="codigo_activacion" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" required></label>
      <div class="modal-plan-acciones">
        <button type="submit" class="btn-primario">Activar</button>
      </div>
    </form>
    <form method="POST" action="{{ route('perfil.2fa.cancelar') }}">
      @csrf
      <button type="submit" class="btn-secundario">Cancelar</button>
    </form>
  @else
    <p>Protege tu cuenta de administrador: además de la contraseña, se pedirá un código que cambia cada 30 segundos en tu celular.</p>
    <form method="POST" action="{{ route('perfil.2fa.iniciar') }}">
      @csrf
      <div class="modal-plan-acciones">
        <button type="submit" class="btn-primario">Configurar verificación en dos pasos</button>
      </div>
    </form>
  @endif
</section>
@endif
@endsection
