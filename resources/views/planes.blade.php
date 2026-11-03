<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes|Suscripciones</title>
      @vite(['resources/js/app.js'])
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
      <div class="pagina-titulo">
        <div>
          <h1>Planes y suscripciones</h1>
          <p>Administración interna de planes. No se realizan compras de autoservicio.</p>
        </div>
        <button class="btn-primario">+ Crear plan</button>
      </div>
 
      <section class="planes">
        <article class="tarjeta-plan">
          <div class="plan-cabecera"><h3>Mensual universitario</h3></div>
          <div class="plan-precio"><strong>$ 35.000</strong><span class="badge verde">Activo</span></div>
          <small>COP · 30 días · Entradas ilimitadas</small>
          <p>Acceso a musculación y actividades.</p>
          <div class="plan-acciones"><button class="btn-borde">Consultar / editar</button><a href="#">Desactivar</a></div>
        </article>
        <article class="tarjeta-plan">
          <div class="plan-cabecera"><h3>Trimestral bienestar</h3></div>
          <div class="plan-precio"><strong>$ 90.000</strong><span class="badge verde">Activo</span></div>
          <small>COP · 90 días · Entradas ilimitadas</small>
          <p>Continuidad de entrenamiento y actividades.</p>
          <div class="plan-acciones"><button class="btn-borde">Consultar / editar</button><a href="#">Desactivar</a></div>
        </article>
        <article class="tarjeta-plan">
          <div class="plan-cabecera"><h3>Ingreso ocasional</h3></div>
          <div class="plan-precio"><strong>$ 5.000</strong><span class="badge gris">Inactivo</span></div>
          <small>COP · 1 día · Una entrada</small>
          <p>Acceso único al área de musculación.</p>
          <div class="plan-acciones"><button class="btn-borde">Consultar / editar</button><a href="#">Activar</a></div>
        </article>
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
          <select><option>Estado: todos</option><option>Activa</option><option>Por activar</option><option>Vencida</option></select>
          <select><option>Plan: todos</option><option>Mensual universitario</option><option>Trimestral bienestar</option></select>
        </div>
 
        <table class="tabla">
          <thead>
            <tr><th>Usuario</th><th>Plan</th><th>Inicio</th><th>Vencimiento</th><th>Pago</th><th>Suscripción</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <tr><td>Mariana López</td><td>Mensual universitario</td><td>02 oct 2026</td><td>31 oct 2026</td>
                <td><span class="badge verde">Verificado</span></td><td><span class="badge verde">Activa</span></td><td class="iconos">👁 ✎ ⋯</td></tr>
            <tr><td>Diego Ramírez</td><td>Mensual universitario</td><td>22 oct 2026</td><td>20 nov 2026</td>
                <td><span class="badge amarillo">Pendiente</span></td><td><span class="badge amarillo">Por activar</span></td><td class="iconos">👁 ✎ ⋯</td></tr>
            <tr><td>Mateo Torres</td><td>Mensual universitario</td><td>16 sep 2026</td><td>15 oct 2026</td>
                <td><span class="badge verde">Verificado</span></td><td><span class="badge rojo">Vencida</span></td><td class="iconos">👁 ✎ ⋯</td></tr>
            <tr><td>Sara Restrepo</td><td>Trimestral bienestar</td><td>03 oct 2026</td><td>31 dic 2026</td>
                <td><span class="badge verde">Verificado</span></td><td><span class="badge verde">Activa</span></td><td class="iconos">👁 ✎ ⋯</td></tr>
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


</body>
</html>