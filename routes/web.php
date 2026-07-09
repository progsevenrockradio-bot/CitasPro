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
    return view('app');
});

// Sitemap
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Ruta pública para el perfil del profesional y reservas de clientes
Route::get('/p/{id}', [\App\Http\Controllers\ClienteWebController::class, 'perfil'])->name('cliente.perfil');

// Rutas públicas para el formulario de opiniones de clientes
Route::get('/opinion/{codigo}', [\App\Http\Controllers\ClienteWebController::class, 'resenaForm'])->name('cliente.resena_form');
Route::post('/opinion/{codigo}', [\App\Http\Controllers\ClienteWebController::class, 'resenaSubmit'])->name('cliente.resena_submit');

// SPA Frontend para Profesionales (Vue 3)
Route::get('/panel/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/login', function () {
    return view('app');
})->name('login');

Route::get('/registro', function () {
    return view('app');
})->name('registro');

// ── Páginas Públicas de Reserva (SPA, sin autenticación) ────────────────────


// Página de reserva pública de un negocio: /{slug}/book
// El profesional comparte este link con sus clientes
Route::get('/{slug}/book', function () {
    return view('app');
})->where('slug', '[a-z0-9\-]+')->name('reserva.publica');

// Auth::routes();

// ── Páginas Legales ────────────────────────────────────────────────────
Route::view('/aviso-legal', 'legal.aviso-legal')->name('legal.aviso');
Route::view('/politica-privacidad', 'legal.politica-privacidad')->name('legal.privacidad');
Route::view('/politica-cookies', 'legal.politica-cookies')->name('legal.cookies');
Route::view('/terminos-condiciones', 'legal.terminos-generales')->name('legal.terminos');
Route::view('/condiciones-clientes', 'legal.condiciones-clientes')->name('legal.clientes');
Route::view('/contrato', 'legal.contrato-profesionales')->name('legal.contrato');
Route::view('/dpa', 'legal.dpa')->name('legal.dpa');

// Permitir que el router de la SPA maneje todas las demás rutas web al refrescar (F5)
Route::fallback(function () {
    return view('app');
});

