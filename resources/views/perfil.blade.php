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

<section class="tarjeta" id="doble-factor">
  <h2>Verificación en dos pasos</h2>
  @if ($cuenta->doble_factor_activo)
    <p>Estado: <strong>activa</strong>. Al iniciar sesión se pide el código de tu app de autenticación.</p>
    <form method="POST" action="{{ route('perfil.2fa.desactivar') }}" data-confirmar="¿Desactivar la verificación en dos pasos? Tu cuenta quedará protegida solo con la contraseña.">
      @csrf
      @method('DELETE')
      <label class="campo-plan"><span>Contraseña actual *</span><input type="password" name="clave_actual_2fa" autocomplete="current-password" required></label>
      <div class="modal-plan-acciones">
        <button type="submit" class="btn-borde">Desactivar</button>
      </div>
    </form>
  @elseif ($nuevoSecreto)
    <div class="doble-factor-qr">
      <figure>
        {!! \App\Support\Totp::qrSvg(\App\Support\Totp::uri($nuevoSecreto, $cuenta->login)) !!}
        <figcaption>Escanea este código con tu celular</figcaption>
      </figure>
      <ol class="nota-plan">
        <li>Instala en tu celular Google Authenticator, Microsoft Authenticator o Authy.</li>
        <li>En la app toca <em>+</em> y elige <em>Escanear un código QR</em>.</li>
        <li>Apunta la cámara a este código. Aparecerá <strong>SIGIP ({{ $cuenta->login }})</strong> con un código de 6 dígitos que cambia cada 30 segundos.</li>
        <li>Escribe ese código abajo y toca <em>Activar</em>.</li>
      </ol>
    </div>
    <details class="clave-manual">
      <summary>¿No puedes escanear el código? Ingresa la clave a mano</summary>
      <p>Elige <em>Ingresar clave de configuración</em>, cuenta <strong>SIGIP:{{ $cuenta->login }}</strong>, basada en tiempo:</p>
      <p class="clave-2fa"><code>{{ \App\Support\Totp::formatear($nuevoSecreto) }}</code></p>
    </details>
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
      <button type="submit" class="btn-borde">Cancelar</button>
    </form>
  @else
    <p>Estado: <strong>inactiva</strong> (es opcional). Si la activas, además de la contraseña se pedirá un código que cambia cada 30 segundos en tu celular. Así, aunque alguien conozca tu contraseña, no podrá entrar sin tu celular.</p>
    <form method="POST" action="{{ route('perfil.2fa.iniciar') }}">
      @csrf
      <div class="modal-plan-acciones">
        <button type="submit" class="btn-primario">Configurar verificación en dos pasos</button>
      </div>
    </form>
  @endif
</section>
@endsection
