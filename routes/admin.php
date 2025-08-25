<?php

use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\OrderPdfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    // Rutas para gestión de imágenes de productos
    Route::prefix('products/{product}/images')->name('admin.products.images.')->group(function () {
        Route::post('/', [ProductImageController::class, 'store'])->name('store');
        Route::put('/{image}', [ProductImageController::class, 'update'])->name('update');
        Route::delete('/{image}', [ProductImageController::class, 'destroy'])->name('destroy');
        Route::post('/{image}/primary', [ProductImageController::class, 'setPrimary'])->name('primary');
        Route::post('/reorder', [ProductImageController::class, 'reorder'])->name('reorder');
    });

    // Ruta para exportar pedidos a PDF
    Route::get('/orders/{order}/pdf', [OrderPdfController::class, 'download'])->name('orders.pdf');
});
