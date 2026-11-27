<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\DobleFactorController;
use App\Http\Controllers\ControlAccesoController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\OperadorController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PortalInstructorController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\UsuarioController;

// Estado del sistema para monitoreo (ISO 25010 – Fiabilidad / Disponibilidad).
// Responde 200 si la aplicación y la base de datos funcionan; no expone detalles.
Route::get('/up', function () {
    try {
        \Illuminate\Support\Facades\DB::select('select 1');

        return response()->json(['estado' => 'ok']);
    } catch (\Throwable) {
        return response()->json(['estado' => 'error'], 503);
    }
})->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \App\Http\Middleware\ValidarTokenCsrf::class])->name('estado');

// Ley 1581 de 2012: política de tratamiento de datos personales (pública).
Route::view('/politica-de-datos', 'politica-datos')->name('politica-datos');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('index')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
| Todas requieren iniciar sesión. Según el tipo de cuenta:
|  - Administrador: todo.
|  - Operador: la operación diaria (usuarios, accesos, pagos, préstamos,
|    reservas, inscripciones y asistencia).
|  - Instructor: su portal (horario, reservas y actividades propias).
*/

Route::middleware('auth')->group(function () {

    // Página de inicio según el tipo de cuenta.
    Route::get('/index', function () {
        return auth()->user()->tieneTipo('Instructor')
            ? redirect()->route('portal.horario')
            : redirect()->route('inicio');
    })->name('index');

    // Inicio del personal: bienvenida, indicadores y gráficas según el rol.
    Route::get('/inicio', [InicioController::class, 'index'])
        ->middleware('tipo_cuenta:Administrador,Operador')
        ->name('inicio');

    Route::get('/dashboard', fn () => redirect()->route('index'))->name('dashboard');

    // HU-189: mis datos personales y contraseña (cualquier cuenta).
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/clave', [PerfilController::class, 'cambiarClave'])->name('perfil.clave');

    // ISO 25010 – Autenticidad: doble factor (TOTP) opcional para todas las cuentas.
    Route::post('/perfil/doble-factor/iniciar', [DobleFactorController::class, 'iniciarActivacion'])->name('perfil.2fa.iniciar');
    Route::post('/perfil/doble-factor/activar', [DobleFactorController::class, 'activar'])->middleware('throttle:10,1')->name('perfil.2fa.activar');
    Route::post('/perfil/doble-factor/cancelar', [DobleFactorController::class, 'cancelarActivacion'])->name('perfil.2fa.cancelar');
    Route::delete('/perfil/doble-factor', [DobleFactorController::class, 'desactivar'])->middleware('throttle:10,1')->name('perfil.2fa.desactivar');

    // Detalle de una actividad programada y su asistencia (HU-180, 181, 184).
    // El instructor solo puede abrir sus propias actividades.
    Route::middleware('tipo_cuenta:Administrador,Operador,Instructor')->group(function () {
        Route::get('/programaciones/{id}', [ActividadController::class, 'show'])->whereNumber('id')->name('programaciones.show');
        Route::post('/programaciones/{id}/asistencia', [ActividadController::class, 'asistencia'])->whereNumber('id')->name('programaciones.asistencia');
    });

    /*
    |----------------------------------------------------------------------
    | Portal del instructor
    |----------------------------------------------------------------------
    */
    Route::middleware('tipo_cuenta:Instructor')->prefix('mi')->name('portal.')->group(function () {
        Route::get('/horario', [PortalInstructorController::class, 'horario'])->name('horario');

        Route::get('/reservas', [PortalInstructorController::class, 'reservas'])->name('reservas');
        Route::post('/reservas', [PortalInstructorController::class, 'solicitarReserva'])->name('reservas.store');
        Route::put('/reservas/{id}', [PortalInstructorController::class, 'modificarReserva'])->whereNumber('id')->name('reservas.update');
        Route::patch('/reservas/{id}/cancelar', [PortalInstructorController::class, 'cancelarReserva'])->whereNumber('id')->name('reservas.cancelar');

        Route::get('/actividades', [PortalInstructorController::class, 'actividades'])->name('actividades');
        Route::post('/actividades', [PortalInstructorController::class, 'solicitarActividad'])->name('actividades.store');
        Route::put('/actividades/{id}', [PortalInstructorController::class, 'modificarActividad'])->whereNumber('id')->name('actividades.update');
        Route::patch('/actividades/{id}/cancelar', [PortalInstructorController::class, 'cancelarActividad'])->whereNumber('id')->name('actividades.cancelar');
    });

    /*
    |----------------------------------------------------------------------
    | Administrador y Operador
    |----------------------------------------------------------------------
    */
    Route::middleware('tipo_cuenta:Administrador,Operador')->group(function () {

        // Fotos de usuarios y espacios (disco privado, solo personal autenticado).
        Route::get('/imagenes/{carpeta}/{archivo}', [ImagenController::class, 'ver'])->whereIn('carpeta', ['usuarios', 'espacios'])->where('archivo', '[0-9a-f\-]{36}\.(jpg|jpeg|png|webp)')->name('imagenes.ver');

        // HU-199 / HU-204: notificaciones de solicitudes.
        Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones');

        // Historial de cambios de un pago, una suscripción o una reserva.
        Route::get('/historial/{modulo}/{id}', [AuditoriaController::class, 'registro'])->whereIn('modulo', ['pago', 'suscripcion', 'reserva'])->whereNumber('id')->name('historial.registro');

        // Usuarios
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios');
        Route::get('/usuarios/exportar', [UsuarioController::class, 'exportar'])->name('usuarios.exportar');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->whereNumber('id')->name('usuarios.update');
        Route::patch('/usuarios/{id}/estado', [UsuarioController::class, 'cambiarEstado'])->whereNumber('id')->name('usuarios.estado');
        Route::get('/usuarios/{id}/historial', [UsuarioController::class, 'historial'])->whereNumber('id')->name('usuarios.historial');

        // Control de acceso
        Route::get('/control-acceso', [ControlAccesoController::class, 'index'])->name('control-acceso.index');
        Route::get('/control-acceso/consultar', [ControlAccesoController::class, 'consultar'])->name('control-acceso.consultar');
        Route::get('/control-acceso/historial', [ControlAccesoController::class, 'historial'])->name('control-acceso.historial');
        Route::get('/control-acceso/exportar', [ControlAccesoController::class, 'exportar'])->name('control-acceso.exportar');
        Route::post('/control-acceso/registrar-ingreso', [ControlAccesoController::class, 'registrarIngreso'])->name('control-acceso.registrar-ingreso');
        Route::post('/control-acceso/salida/{usuario_id}', [ControlAccesoController::class, 'registrarSalida'])->whereNumber('usuario_id')->name('control-acceso.salida');

        // Préstamos
        Route::get('/prestamos', [PrestamoController::class, 'index'])->name('prestamos');
        Route::get('/prestamos/exportar', [PrestamoController::class, 'exportar'])->name('prestamos.exportar');
        Route::get('/prestamos/{id}', [PrestamoController::class, 'show'])->whereNumber('id')->name('prestamos.show');
        Route::post('/prestamos', [PrestamoController::class, 'store'])->name('prestamos.store');
        Route::post('/prestamos/{prestamoId}/devolucion', [PrestamoController::class, 'devolver'])->whereNumber('prestamoId')->name('prestamos.devolver');

        // Inventario (consulta)
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('/inventario/exportar', [InventarioController::class, 'exportar'])->name('inventario.exportar');
        Route::get('/inventario/{tipo}/{id}', [InventarioController::class, 'show'])->whereIn('tipo', ['maquina', 'implemento'])->whereNumber('id')->name('inventario.show');
        Route::post('/inventario/maquina/{id}/novedades', [InventarioController::class, 'registrarNovedad'])->whereNumber('id')->name('inventario.novedades.store');
        Route::patch('/inventario/maquina/{id}/novedades/{novedad}', [InventarioController::class, 'actualizarNovedad'])->whereNumber(['id', 'novedad'])->name('inventario.novedades.update');

        // Planes y suscripciones
        Route::get('/planes', [PlanController::class, 'index'])->name('planes.index');
        Route::get('/planes/{id}/historial', [PlanController::class, 'historial'])->whereNumber('id')->name('planes.historial');
        Route::get('/suscripciones', fn () => redirect()->route('planes.index'))->name('suscripciones.index');
        Route::post('/suscripciones', [SuscripcionController::class, 'store'])->name('suscripciones.store');
        Route::put('/suscripciones/{id}', [SuscripcionController::class, 'update'])->whereNumber('id')->name('suscripciones.update');

        // Pagos
        Route::get('/pago', [PagoController::class, 'index'])->name('pago.index');
        Route::post('/pago', [PagoController::class, 'store'])->name('pago.store');
        Route::patch('/pago/{id}/revision', [PagoController::class, 'revisar'])->whereNumber('id')->name('pago.revisar');

        // Espacios (consulta)
        Route::get('/espacios', [EspacioController::class, 'index'])->name('espacios.index');
        Route::get('/espacios/{id}', [EspacioController::class, 'show'])->whereNumber('id')->name('espacios.show');

        // Reservas
        Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
        Route::get('/reservas/historial', [ReservaController::class, 'historial'])->name('reservas.historial');
        Route::get('/reservas/exportar', [ReservaController::class, 'exportar'])->name('reservas.exportar');
        Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
        Route::patch('/reservas/{id}/responder', [ReservaController::class, 'responder'])->whereNumber('id')->name('reservas.responder');
        Route::patch('/reservas/{id}/cancelar', [ReservaController::class, 'cancelar'])->whereNumber('id')->name('reservas.cancelar');

        // Actividades: consulta, inscripciones y reportes
        Route::get('/actividades', [ActividadController::class, 'index'])->name('actividades.index');
        Route::get('/actividades/participante', [ActividadController::class, 'participante'])->name('actividades.participante');
        Route::get('/actividades/reporte', [ActividadController::class, 'reporte'])->name('actividades.reporte');
        Route::get('/actividades/reporte/exportar', [ActividadController::class, 'exportarReporte'])->name('actividades.reporte.exportar');
        Route::post('/programaciones/{id}/inscripciones', [ActividadController::class, 'inscribir'])->whereNumber('id')->name('programaciones.inscribir');
        Route::delete('/programaciones/{id}/inscripciones/{usuario}', [ActividadController::class, 'desinscribir'])->whereNumber(['id', 'usuario'])->name('programaciones.desinscribir');
    });

    /*
    |----------------------------------------------------------------------
    | Solo Administrador
    |----------------------------------------------------------------------
    */
    Route::middleware('tipo_cuenta:Administrador')->group(function () {

        // Inventario
        Route::post('/inventario/registrar', [InventarioController::class, 'store'])->name('inventario.store');
        Route::put('/inventario/{tipo}/{id}', [InventarioController::class, 'update'])->whereIn('tipo', ['maquina', 'implemento'])->whereNumber('id')->name('inventario.update');

        // Planes
        Route::post('/planes', [PlanController::class, 'store'])->name('planes.store');
        Route::put('/planes/{id}', [PlanController::class, 'update'])->whereNumber('id')->name('planes.update');
        Route::delete('/planes/{id}', [PlanController::class, 'destroy'])->whereNumber('id')->name('planes.destroy');
        Route::patch('/planes/{id}/activar', [PlanController::class, 'activar'])->whereNumber('id')->name('planes.activar');

        // Espacios
        Route::post('/espacios', [EspacioController::class, 'store'])->name('espacios.store');
        Route::patch('/espacios/{id}/estado', [EspacioController::class, 'cambiarEstado'])->whereNumber('id')->name('espacios.estado');
        Route::post('/espacios/{id}/imagen', [EspacioController::class, 'cambiarImagen'])->whereNumber('id')->name('espacios.imagen');
        Route::post('/espacios/{id}/mantenimientos', [EspacioController::class, 'registrarMantenimiento'])->whereNumber('id')->name('espacios.mantenimientos.store');
        Route::patch('/espacios/{id}/mantenimientos/{mantenimiento}/finalizar', [EspacioController::class, 'finalizarMantenimiento'])->whereNumber(['id', 'mantenimiento'])->name('espacios.mantenimientos.finalizar');

        // Instructores
        Route::get('/instructores', [InstructorController::class, 'index'])->name('instructores.index');
        Route::get('/instructores/exportar', [InstructorController::class, 'exportar'])->name('instructores.exportar');
        Route::post('/instructores', [InstructorController::class, 'store'])->name('instructores.store');
        Route::put('/instructores/{id}', [InstructorController::class, 'update'])->whereNumber('id')->name('instructores.update');
        Route::patch('/instructores/{id}/estado', [InstructorController::class, 'cambiarEstado'])->whereNumber('id')->name('instructores.estado');
        Route::post('/instructores/{id}/cuenta', [InstructorController::class, 'crearCuenta'])->whereNumber('id')->name('instructores.cuenta');
        Route::get('/instructores/{id}/historial', [InstructorController::class, 'historial'])->whereNumber('id')->name('instructores.historial');
        Route::get('/instructores/{id}/horario', [InstructorController::class, 'horario'])->whereNumber('id')->name('instructores.horario');
        Route::post('/instructores/{id}/turnos', [InstructorController::class, 'asignarTurno'])->whereNumber('id')->name('instructores.turnos');
        Route::patch('/instructores/{id}/turnos/{turno}/cancelar', [InstructorController::class, 'cancelarTurno'])->whereNumber(['id', 'turno'])->name('instructores.turnos.cancelar');

        // Operadores
        Route::get('/operadores', [OperadorController::class, 'index'])->name('operadores.index');
        Route::get('/operadores/historial', [OperadorController::class, 'movimientos'])->name('operadores.movimientos');
        Route::post('/operadores', [OperadorController::class, 'store'])->name('operadores.store');

        // Auditoría del sistema: cambios en registros, acciones de las cuentas e integridad.
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria');
        Route::patch('/operadores/{id}/estado', [OperadorController::class, 'cambiarEstado'])->whereNumber('id')->name('operadores.estado');

        // Actividades: catálogo, programación y validación
        Route::post('/actividades', [ActividadController::class, 'store'])->name('actividades.store');
        Route::put('/actividades/{id}', [ActividadController::class, 'update'])->whereNumber('id')->name('actividades.update');
        Route::post('/programaciones', [ActividadController::class, 'programar'])->name('programaciones.store');
        Route::put('/programaciones/{id}', [ActividadController::class, 'actualizarProgramacion'])->whereNumber('id')->name('programaciones.update');
        Route::patch('/programaciones/{id}/cancelar', [ActividadController::class, 'cancelarProgramacion'])->whereNumber('id')->name('programaciones.cancelar');
        Route::patch('/programaciones/{id}/validar', [ActividadController::class, 'validar'])->whereNumber('id')->name('programaciones.validar');
    });
});

require __DIR__.'/auth.php';
