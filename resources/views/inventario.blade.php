<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGIP | Inventario</title>
  @vite(['resources/js/app.js'])
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  
</head>

<body>
  <div class="flexbox">
 <x-panel-izq />
    <div class="contenido">
      <x-nav_bar />
        <main class="pagina">
        <div class="pagina-titulo">
          <div>
            <h1>Inventario</h1>
            <p>Catálogo de máquinas e implementos. Las máquinas no son unidades prestables.</p>
          </div>
          <button class="btn-primario">+ Registrar elemento</button>
        </div>

        <div class="resumen">
          <span class="chip morado">21 máquinas</span>
          <span class="chip verde">18 disponibles</span>
          <span class="chip amarillo">2 en mantenimiento</span>
          <span class="chip rojo">1 fuera de servicio</span>
          <span class="resumen-texto">38 implementos · 30 disponibles</span>
        </div>

        <section class="tarjeta">
          <div class="pestanas">
            <button class="activa">Todos los elementos</button>
            <button>Máquinas (21)</button>
            <button>Implementos (38 unidades)</button>
          </div>

          <div class="filtros">
            <input type="search" placeholder="Buscar nombre o código">
            <select>
              <option>Categoría: todas</option>
              <option>Máquina</option>
              <option>Entrenamiento</option>
              <option>Resistencia</option>
            </select>
            <select>
              <option>Ubicación: todas</option>
              <option>Musculación</option>
              <option>Bodega A</option>
            </select>
            <select>
              <option>Estado: todos</option>
              <option>Disponible</option>
              <option>En mantenimiento</option>
              <option>Fuera de servicio</option>
            </select>
          </div>

          <table class="tabla">
            <thead>
              <tr>
                <th>Código / nombre</th>
                <th>Categoría</th>
                <th>Ubicación</th>
                <th>Cantidad</th>
                <th>Estado operativo</th>
                <th>Últ. mantenimiento</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>CM-03 · Caminadora</td>
                <td>Máquina</td>
                <td>Musculación</td>
                <td>No prestable</td>
                <td><span class="badge rojo ancho">Fuera de servicio</span></td>
                <td>22 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>EL-02 · Elíptica</td>
                <td>Máquina</td>
                <td>Musculación</td>
                <td>No prestable</td>
                <td><span class="badge amarillo ancho">En mantenimiento</span></td>
                <td>21 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>BC-04 · Bicicleta</td>
                <td>Máquina</td>
                <td>Musculación</td>
                <td>No prestable</td>
                <td><span class="badge amarillo ancho">En mantenimiento</span></td>
                <td>21 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>PR-01 · Prensa de piernas</td>
                <td>Máquina</td>
                <td>Musculación</td>
                <td>No prestable</td>
                <td><span class="badge verde ancho">Disponible</span></td>
                <td>10 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>IMP-01 · Colchoneta</td>
                <td>Entrenamiento</td>
                <td>Bodega A</td>
                <td>12 total / 8 disp.</td>
                <td><span class="badge morado ancho">2 prestadas</span><small class="alerta">2 en mantenimiento</small></td>
                <td>20 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>IMP-02 · Banda elástica</td>
                <td>Resistencia</td>
                <td>Bodega A</td>
                <td>20 total / 16 disp.</td>
                <td><span class="badge morado ancho">4 prestadas</span></td>
                <td>15 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
              <tr>
                <td>IMP-03 · Balón medicinal</td>
                <td>Entrenamiento</td>
                <td>Bodega A</td>
                <td>6 total / 6 disp.</td>
                <td><span class="badge verde ancho">Disponible</span></td>
                <td>15 oct 2026</td>
                <td class="iconos">👁 ✎ ⋯</td>
              </tr>
            </tbody>
          </table>

          <div class="paginacion">
            <small>Mostrando 1–7 de 24 registros</small>
            <div>
              <button class="btn-pag" disabled>Anterior</button>
              <button class="btn-pag actual">1</button>
              <button class="btn-pag">2</button>
              <button class="btn-pag">Siguiente</button>
            </div>
          </div>
        </section>

        <p class="observacion">Observación seleccionada CM-03: banda de arrastre irregular; uso bloqueado hasta cierre de mantenimiento. Detalle completo disponible en consulta.</p>
      </main>
    </div>
  </div>
</body>

</html>