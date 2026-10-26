<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EspecialidadController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\BloqueoController;
use App\Http\Controllers\Admin\AnuncioController;
use App\Http\Controllers\Admin\LandingContenidoController;
use App\Http\Controllers\Admin\LandingGaleriaController;
use App\Http\Controllers\Admin\LandingTestimoniosController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Admin\CitaController as AdminCitaController;
use App\Http\Controllers\Admin\PacienteController;
use App\Http\Controllers\Admin\PersonalController;
use App\Http\Controllers\Asistente\DashboardController as AsistenteDashboardController;
use App\Http\Controllers\Asistente\CitaController as AsistenteCitaController;
use App\Http\Controllers\Asistente\PacienteController as AsistentePacienteController;
use App\Http\Controllers\Paciente\CitaController;
use App\Http\Controllers\Paciente\DashboardController as PacienteDashboardController;
use App\Http\Controllers\Paciente\PerfilController;

// Página principal
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Autenticación
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Registro
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Recuperación de contraseña
use App\Http\Controllers\Auth\PasswordResetController;

Route::get('/forgot-password', [PasswordResetController::class, 'solicitarFormulario'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'enviarLink'])->middleware('throttle:3,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'restablecerFormulario'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'restablecer'])->name('password.update');

// Verificación de correo (opcional, no bloquea el acceso)
Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('paciente.dashboard')->with('success', '¡Tu correo fue verificado correctamente!');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    // Si el servicio de correo falla, se avisa sin error 500
    if (!$request->user()->enviarVerificacion()) {
        return back()->with('error', 'No pudimos enviar el correo en este momento. Intenta más tarde.');
    }

    return back()->with('success', 'Te reenviamos el correo de verificación. Revisa tu bandeja.');
})->middleware(['auth', 'throttle:3,1'])->name('verification.send');

// Rutas Admin — solo doctora
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Especialidades
    Route::get('/especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
    Route::post('/especialidades', [EspecialidadController::class, 'store'])->name('especialidades.store');
    Route::put('/especialidades/{id}', [EspecialidadController::class, 'update'])->name('especialidades.update');
    Route::patch('/especialidades/{id}/toggle', [EspecialidadController::class, 'toggle'])->name('especialidades.toggle');
    Route::delete('/especialidades/{id}', [EspecialidadController::class, 'destroy'])->name('especialidades.destroy');

    // Servicios
    Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');
    Route::put('/servicios/{id}', [ServicioController::class, 'update'])->name('servicios.update');
    Route::patch('/servicios/{id}/toggle', [ServicioController::class, 'toggle'])->name('servicios.toggle');
    Route::delete('/servicios/{id}', [ServicioController::class, 'destroy'])->name('servicios.destroy');

    // Horarios
    Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
    Route::put('/horarios/{id}', [HorarioController::class, 'update'])->name('horarios.update');
    Route::patch('/horarios/{id}/toggle', [HorarioController::class, 'toggle'])->name('horarios.toggle');
    Route::delete('/horarios/{id}', [HorarioController::class, 'destroy'])->name('horarios.destroy');

    // Landing page: imágenes y textos editables
    Route::get('/landing/contenido', [LandingContenidoController::class, 'contenido'])->name('landing.contenido');
    Route::get('/landing/contacto', [LandingContenidoController::class, 'contacto'])->name('landing.contacto');
    Route::put('/landing/secciones/{seccion}', [LandingContenidoController::class, 'guardar'])->name('landing.guardar');
    Route::delete('/landing/secciones/{seccion}', [LandingContenidoController::class, 'restaurarSeccion'])->name('landing.restaurar-seccion');
    Route::delete('/landing/campos/{clave}', [LandingContenidoController::class, 'restaurarCampo'])
        ->where('clave', '[a-z0-9_.]+')->name('landing.restaurar-campo');

    // Landing page: galería (agregar, quitar y reordenar fotos)
    Route::get('/landing/galeria', [LandingGaleriaController::class, 'index'])->name('landing.galeria');
    Route::post('/landing/galeria', [LandingGaleriaController::class, 'agregar'])->name('landing.galeria.agregar');
    Route::delete('/landing/galeria', [LandingGaleriaController::class, 'restaurar'])->name('landing.galeria.restaurar');
    Route::put('/landing/galeria/{item}', [LandingGaleriaController::class, 'actualizar'])->where('item', '[a-z0-9-]+')->name('landing.galeria.actualizar');
    Route::delete('/landing/galeria/{item}', [LandingGaleriaController::class, 'eliminar'])->where('item', '[a-z0-9-]+')->name('landing.galeria.eliminar');
    Route::patch('/landing/galeria/{item}/subir', [LandingGaleriaController::class, 'subir'])->where('item', '[a-z0-9-]+')->name('landing.galeria.subir');
    Route::patch('/landing/galeria/{item}/bajar', [LandingGaleriaController::class, 'bajar'])->where('item', '[a-z0-9-]+')->name('landing.galeria.bajar');
    Route::delete('/landing/galeria/{item}/imagen', [LandingGaleriaController::class, 'restaurarImagen'])->where('item', '[a-z0-9-]+')->name('landing.galeria.restaurar-imagen');

    // Landing page: opiniones de pacientes (sin fotos; solo se publican las autorizadas)
    Route::get('/landing/opiniones', [LandingTestimoniosController::class, 'index'])->name('landing.testimonios');
    Route::post('/landing/opiniones', [LandingTestimoniosController::class, 'agregar'])->name('landing.testimonios.agregar');
    Route::put('/landing/opiniones/{item}', [LandingTestimoniosController::class, 'actualizar'])->where('item', '[0-9]+')->name('landing.testimonios.actualizar');
    Route::delete('/landing/opiniones/{item}', [LandingTestimoniosController::class, 'eliminar'])->where('item', '[0-9]+')->name('landing.testimonios.eliminar');
    Route::patch('/landing/opiniones/{item}/subir', [LandingTestimoniosController::class, 'subir'])->where('item', '[0-9]+')->name('landing.testimonios.subir');
    Route::patch('/landing/opiniones/{item}/bajar', [LandingTestimoniosController::class, 'bajar'])->where('item', '[0-9]+')->name('landing.testimonios.bajar');

    // Landing page: anuncios y promociones
    Route::get('/anuncios', [AnuncioController::class, 'index'])->name('anuncios.index');
    Route::get('/anuncios/crear', [AnuncioController::class, 'create'])->name('anuncios.create');
    Route::post('/anuncios', [AnuncioController::class, 'store'])->name('anuncios.store');
    Route::get('/anuncios/{id}/editar', [AnuncioController::class, 'edit'])->name('anuncios.edit');
    Route::put('/anuncios/{id}', [AnuncioController::class, 'update'])->name('anuncios.update');
    Route::patch('/anuncios/{id}/toggle', [AnuncioController::class, 'toggle'])->name('anuncios.toggle');
    Route::patch('/anuncios/{id}/subir', [AnuncioController::class, 'subir'])->name('anuncios.subir');
    Route::patch('/anuncios/{id}/bajar', [AnuncioController::class, 'bajar'])->name('anuncios.bajar');
    Route::delete('/anuncios/{id}', [AnuncioController::class, 'destroy'])->name('anuncios.destroy');

    // Bloqueos de agenda
    Route::get('/bloqueos', [BloqueoController::class, 'index'])->name('bloqueos.index');
    Route::post('/bloqueos', [BloqueoController::class, 'store'])->name('bloqueos.store');
    Route::delete('/bloqueos/{id}', [BloqueoController::class, 'destroy'])->name('bloqueos.destroy');

    // Citas
    Route::get('/citas', [AdminCitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/crear', [AdminCitaController::class, 'create'])->name('citas.create');
    Route::post('/citas/disponibilidad', [AdminCitaController::class, 'disponibilidad'])->name('citas.disponibilidad');
    Route::patch('/citas/{id}/reagendar', [AdminCitaController::class, 'reagendar'])->name('citas.reagendar');
    Route::post('/citas', [AdminCitaController::class, 'store'])->name('citas.store');
    Route::patch('/citas/{id}/confirmar', [AdminCitaController::class, 'confirmar'])->name('citas.confirmar');
    Route::patch('/citas/{id}/atender', [AdminCitaController::class, 'atender'])->name('citas.atender');
    Route::put('/citas/{id}/nota', [AdminCitaController::class, 'guardarNota'])->name('citas.nota');
    Route::patch('/citas/{id}/cancelar', [AdminCitaController::class, 'cancelar'])->name('citas.cancelar');

    // Pacientes
    Route::get('/pacientes', [PacienteController::class, 'index'])->name('pacientes.index');
    Route::get('/pacientes/{id}', [PacienteController::class, 'show'])->name('pacientes.show');
    Route::get('/pacientes/{id}/imprimir', [PacienteController::class, 'imprimir'])->name('pacientes.imprimir');
    Route::put('/pacientes/{id}', [PacienteController::class, 'update'])->name('pacientes.update');
    Route::put('/pacientes/{id}/expediente', [PacienteController::class, 'actualizarExpediente'])->name('pacientes.expediente.update');
    Route::put('/pacientes/{id}/odontograma', [PacienteController::class, 'actualizarOdontograma'])->name('pacientes.odontograma.update');
    Route::patch('/pacientes/{id}/toggle', [PacienteController::class, 'toggle'])->name('pacientes.toggle');
    Route::patch('/pacientes/{id}/restablecer-password', [PacienteController::class, 'restablecerPassword'])->name('pacientes.restablecer-password');

    // Personal: cuentas de doctora y asistente
    Route::get('/personal', [PersonalController::class, 'index'])->name('personal.index');
    Route::post('/personal', [PersonalController::class, 'store'])->name('personal.store');
    Route::put('/personal/{id}', [PersonalController::class, 'update'])->name('personal.update');
    Route::patch('/personal/{id}/toggle', [PersonalController::class, 'toggle'])->name('personal.toggle');
    Route::patch('/personal/{id}/restablecer-password', [PersonalController::class, 'restablecerPassword'])->name('personal.restablecer-password');
});

// Rutas Asistente — solo asistente
Route::middleware(['auth', 'role:asistente'])->prefix('asistente')->name('asistente.')->group(function () {
    Route::get('/dashboard', [AsistenteDashboardController::class, 'index'])->name('dashboard');

    // Citas
    Route::get('/citas', [AsistenteCitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/crear', [AsistenteCitaController::class, 'create'])->name('citas.create');
    Route::post('/citas/disponibilidad', [AsistenteCitaController::class, 'disponibilidad'])->name('citas.disponibilidad');
    Route::patch('/citas/{id}/reagendar', [AsistenteCitaController::class, 'reagendar'])->name('citas.reagendar');
    Route::post('/citas', [AsistenteCitaController::class, 'store'])->name('citas.store');
    Route::patch('/citas/{id}/confirmar', [AsistenteCitaController::class, 'confirmar'])->name('citas.confirmar');
    Route::patch('/citas/{id}/atender', [AsistenteCitaController::class, 'atender'])->name('citas.atender');
    Route::patch('/citas/{id}/cancelar', [AsistenteCitaController::class, 'cancelar'])->name('citas.cancelar');
    Route::patch('/pacientes/{id}/restablecer-password', [AsistentePacienteController::class, 'restablecerPassword'])->name('pacientes.restablecer-password');
});

// Rutas Paciente — solo paciente
Route::middleware(['auth', 'role:paciente'])->prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');

    // Mi perfil: datos, contraseña y expediente en solo lectura
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::put('/perfil', [PerfilController::class, 'actualizarDatos'])->name('perfil.datos');
    Route::put('/perfil/password', [PerfilController::class, 'cambiarPassword'])->name('perfil.password');

    // Citas
    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/agendar', [CitaController::class, 'create'])->name('citas.create');
    Route::post('/citas/disponibilidad', [CitaController::class, 'disponibilidad'])->name('citas.disponibilidad');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::patch('/citas/{id}/confirmar', [CitaController::class, 'confirmar'])->name('citas.confirmar');
    Route::patch('/citas/{id}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar');
});