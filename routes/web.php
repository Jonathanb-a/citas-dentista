<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EspecialidadController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\CitaController as AdminCitaController;
use App\Http\Controllers\Paciente\CitaController;
use App\Http\Controllers\Paciente\DashboardController as PacienteDashboardController;

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

    // Citas
    Route::get('/citas', [AdminCitaController::class, 'index'])->name('citas.index');
    Route::patch('/citas/{id}/confirmar', [AdminCitaController::class, 'confirmar'])->name('citas.confirmar');
    Route::patch('/citas/{id}/atender', [AdminCitaController::class, 'atender'])->name('citas.atender');
    Route::patch('/citas/{id}/cancelar', [AdminCitaController::class, 'cancelar'])->name('citas.cancelar');
});

// Rutas Asistente — solo asistente
Route::middleware(['auth', 'role:asistente'])->prefix('asistente')->name('asistente.')->group(function () {
    Route::get('/dashboard', function () {
        return 'Dashboard Asistente — En construcción';
    })->name('dashboard');
});

// Rutas Paciente — solo paciente
Route::middleware(['auth', 'role:paciente'])->prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');

    // Citas
    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/agendar', [CitaController::class, 'create'])->name('citas.create');
    Route::post('/citas/disponibilidad', [CitaController::class, 'disponibilidad'])->name('citas.disponibilidad');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::patch('/citas/{id}/confirmar', [CitaController::class, 'confirmar'])->name('citas.confirmar');
    Route::patch('/citas/{id}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar');
});