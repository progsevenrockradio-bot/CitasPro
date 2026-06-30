<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Ruta pública para el perfil del profesional y reservas de clientes
Route::get('/p/{id}', [\App\Http\Controllers\ClienteWebController::class, 'perfil'])->name('cliente.perfil');

// Auth::routes();

// Permitir que el router de la SPA maneje todas las demás rutas web al refrescar (F5)
Route::fallback(function () {
    return view('welcome');
});
