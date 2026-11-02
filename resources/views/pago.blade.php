<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIGIP | Pagos QR</title>
@vite('resources/css/app.css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>
    <script src="components/panel_izq.js"></script>
<div class="flexbox">

  <panel-izq activo="pagos" rol="operador"></panel-izq>

  <div class="contenido">
    <header class="topbar">
      <span class="ruta">Gimnasio Universitario / Pagos QR</span>
      <div class="topbar-der">
        <span class="chip-demo">Demostración</span>
        <span class="notificaciones">🔔 <b>3</b></span>
        <div class="usuario">
          <span class="avatar">CR</span>
          <div><strong>Carlos Ruiz</strong><small>Operador</small></div>
        </div>
      </div>
    </header>

    <main class="pagina">
      <div class="pagina-titulo">
        <div>
          <h1>Pagos QR</h1>
          <p>Revisión de comprobantes y referencias · Operación manual</p>
        </div>
      </div>

      <div class="aviso">
        <span class="aviso-icono">ⓘ</span>
        <div>
          <strong>Capturar o escanear un QR NO confirma un pago</strong>
          <p>Comprueba la referencia y el soporte disponible. Registra el resultado manualmente; SIGIP no está conectado a una entidad bancaria.</p>
        </div>
      </div>

      <div class="pagos-grid">
        <!-- Bandeja de pagos -->
        <section class="tarjeta">
          <h2>Bandeja de pagos</h2>
          <small class="tarjeta-sub">7 pendientes · 78 verificados · 3 rechazados en octubre</small>

          <div class="filtros">
            <input type="search" placeholder="Identificación o referencia">
            <select><option>Estado: todos</option><option>Pendiente</option><option>Verificado</option><option>Rechazado</option></select>
          </div>

          <table class="tabla">
            <thead>
              <tr><th>Usuario / concepto</th><th>Valor / fecha</th><th>Referencia</th><th>Estado</th></tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="nombre">Diego Ramírez</span><small>1045678901</small><small>Mensual universitario</small></td>
                <td>$ 35.000<small class="oscuro">22 oct · 09:50</small></td>
                <td>DEMO-2210-07</td>
                <td><span class="badge amarillo largo">Pendiente de verificación</span></td>
              </tr>
              <tr>
                <td><span class="nombre">Mariana López</span><small>1032456789</small><small>Mensual universitario</small></td>
                <td>$ 35.000<small class="oscuro">02 oct · 08:40</small></td>
                <td>DEMO-0210-12</td>
                <td><span class="badge verde largo">Verificado</span></td>
              </tr>
              <tr>
                <td><span class="nombre">Sara Restrepo</span><small>1034567890</small><small>Trimestral bienestar</small></td>
                <td>$ 90.000<small class="oscuro">03 oct · 07:10</small></td>
                <td>DEMO-0310-03</td>
                <td><span class="badge verde largo">Verificado</span></td>
              </tr>
              <tr>
                <td><span class="nombre">Paula Ospina</span><small>1035678902</small><small>Mensual universitario</small></td>
                <td>$ 35.000<small class="oscuro">20 oct · 15:30</small></td>
                <td>DEMO-2010-08</td>
                <td><span class="badge rojo largo">Rechazado</span></td>
              </tr>
            </tbody>
          </table>

          <div class="paginacion">
            <small>Mostrando 1–4 de 88 registros</small>
            <div>
              <button class="btn-pag" disabled>Anterior</button>
              <button class="btn-pag actual">1</button>
              <button class="btn-pag">2</button>
              <button class="btn-pag">Siguiente</button>
            </div>
          </div>
        </section>

        <!-- Detalle de comprobante -->
        <aside class="tarjeta detalle">
          <h2>Detalle de comprobante</h2>
          <small class="tarjeta-sub">DEMO-2210-07 · Seleccionado</small>

          <div class="soporte-demo">
            <span class="soporte-icono">📄</span>
            <strong>SOPORTE DE DEMOSTRACIÓN</strong>
            <span class="soporte-valor">$ 35.000 COP</span>
            <p>Referencia: DEMO-2210-07<br>No válido para realizar pagos.<br>Sin código QR transaccional.</p>
          </div>

          <dl class="datos">
            <dt>Usuario</dt><dd>Diego Ramírez · 1045678901</dd>
            <dt>Responsable de revisión</dt><dd>Sin asignar · Pendiente</dd>
            <dt>Observaciones</dt><dd>Referencia recibida; pendiente de cotejo.</dd>
          </dl>

          <button class="btn-primario">📋 Registrar revisión</button>
          <small class="nota">Verificado: Mariana y Sara / Carlos Ruiz. Rechazado: Paula / soporte ilegible.</small>
        </aside>
      </div>

      <footer class="pie-pagina">07 / 15 ·  Institución Universitaria Pascual Bravo · Corte: 22 oct 2026, 10:30</footer>
    </main>
  </div>
</div>

</body>
</html>