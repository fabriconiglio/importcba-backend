<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OrderPdfController;
use App\Http\Controllers\API\SocialAuthController;

Route::get('/', function () {
    return redirect('/admin');
});

// =============================================
// RUTAS DE AUTENTICACIÓN SOCIAL (requieren sesiones)
// =============================================
Route::prefix('api/v1/auth')->group(function () {
    Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
    Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
});

// Rutas para pedidos - PDF e impresión (requiere autenticación de admin)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/orders/{order}/pdf', [OrderPdfController::class, 'download'])->name('orders.pdf');
    Route::get('/admin/orders/{order}/print', [OrderPdfController::class, 'print'])->name('orders.print');
});
