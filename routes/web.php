<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EspecialidadController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\BloqueoController;
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
Route::get('/', function () {
    return view('welcome');
});

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