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
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Registro
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Dashboard Admin
Route::middleware(['auth'])->group(function () {

    Route::get('/admin/dashboard', function () {
        return 'Dashboard Admin — En construcción';
    })->name('admin.dashboard');

    Route::get('/asistente/dashboard', function () {
        return 'Dashboard Asistente — En construcción';
    })->name('asistente.dashboard');

    Route::get('/paciente/dashboard', function () {
        return 'Dashboard Paciente — En construcción';
    })->name('paciente.dashboard');

});