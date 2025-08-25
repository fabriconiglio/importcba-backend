<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OrderPdfController;

Route::get('/', function () {
    return redirect('/admin');
});

// Ruta para exportar pedidos a PDF (requiere autenticación de admin)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/orders/{order}/pdf', [OrderPdfController::class, 'download'])->name('orders.pdf');
});
