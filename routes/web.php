<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

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
    Route::get('/dashboard', function () {
        return 'Dashboard Admin — En construcción';
    })->name('dashboard');
});

// Rutas Asistente — solo asistente
Route::middleware(['auth', 'role:asistente'])->prefix('asistente')->name('asistente.')->group(function () {
    Route::get('/dashboard', function () {
        return 'Dashboard Asistente — En construcción';
    })->name('dashboard');
});

// Rutas Paciente — solo paciente
Route::middleware(['auth', 'role:paciente'])->prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/dashboard', function () {
        return 'Dashboard Paciente — En construcción';
    })->name('dashboard');
});