<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OrderPdfController;

Route::get('/', function () {
    return redirect('/admin');
});

// Rutas para pedidos - PDF e impresión (requiere autenticación de admin)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/orders/{order}/pdf', [OrderPdfController::class, 'download'])->name('orders.pdf');
    Route::get('/admin/orders/{order}/print', [OrderPdfController::class, 'print'])->name('orders.print');
});
