<!DOCTYPE html>
<html lang="en">
<head>
    <!-- meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- estilos de usuarios y estilos del panel izquierdo -->
    @vite(['resources/js/app.js'])
    <!-- javascrpit del pop-out -->
    
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
    <!-- fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <!-- titulo -->
    <title>Control de Accesos</title>
</head>
<body>
    <div class="flexbox">
        <!-- ABRE PANEL IZQUIERDO -->
        <x-panel-izq />
        <!-- CIERRA PANEL IZQUIERDO -->
        <!-- ----------------------------------------------------------------------------------- -->
        <!-- ABRE PANEL DERECHO -->
        <div class="panel-dere">
            <!-- ABRE NAVBAR -->
                <x-nav_bar />
            <!-- CIERRA NAVBAR -->
            <main class="contenido-principal">
                
            </main>
        </div>
    </div>
</body>
</html>