import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // Dirección IPv4 fija: la Content-Security-Policy no acepta direcciones IPv6 como [::1].
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            // Todos los archivos que las vistas cargan con @vite deben estar
            // aquí para que "npm run build" los incluya en el manifiesto.
            input: [
                'resources/css/app.css',
                'resources/css/inicio.css',
                'resources/js/inicio.js',
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
                'resources/js/sigip.js',
                'resources/js/tablas.js',
            ],
            refresh: true,
        }),
    ],
});
