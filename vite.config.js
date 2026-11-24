import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Todos los archivos que las vistas cargan con @vite deben estar
            // aquí para que "npm run build" los incluya en el manifiesto.
            input: [
                'resources/css/app.css',
                'resources/css/nav_bar.css',
                'resources/css/panel_izq.css',
                'resources/css/styles.css',
                'resources/css/styles2.css',
                'resources/css/styles_control_acceso.css',
                'resources/css/styles_prestamos.css',
                'resources/js/app.js',
                'resources/js/control-acceso.js',
                'resources/js/devolucion-prestamo.js',
                'resources/js/espacios.js',
                'resources/js/planes.js',
                'resources/js/pop_out.js',
                'resources/js/prestamo-categorias.js',
                'resources/js/registrar-inventario.js',
            ],
            refresh: true,
        }),
    ],
});
