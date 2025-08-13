<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\ProductImageController;
use App\Http\Controllers\API\CatalogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Ruta para obtener información del usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas públicas (sin autenticación)
Route::prefix('v1')->group(function () {
    
    // =============================================
    // PRODUCTOS
    // =============================================
    Route::apiResource('products', ProductController::class);
    
    // Rutas adicionales para productos
    Route::get('products/category/{categorySlug}', [ProductController::class, 'byCategory']);
    Route::get('products/featured/list', [ProductController::class, 'featured']);

    // =============================================
    // CATÁLOGO SEO-FRIENDLY
    // =============================================
    Route::prefix('catalog')->group(function () {
        Route::get('category/{categorySlug}', [CatalogController::class, 'byCategory']);
        Route::get('brand/{brandSlug}', [CatalogController::class, 'byBrand']);
        Route::get('category/{categorySlug}/brand/{brandSlug}', [CatalogController::class, 'byCategoryAndBrand']);
    });

    // Rutas para imágenes de productos
    Route::prefix('products/{productId}/images')->group(function () {
        Route::post('/', [ProductImageController::class, 'store']);
        Route::delete('/{imageId}', [ProductImageController::class, 'destroy']);
        Route::put('order', [ProductImageController::class, 'updateOrder']);
        Route::put('{imageId}/primary', [ProductImageController::class, 'setPrimary']);
    });
    
    // =============================================
    // CATEGORÍAS
    // =============================================
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/tree/list', [CategoryController::class, 'tree']);
    
    // =============================================
    // MARCAS
    // =============================================
    Route::apiResource('brands', BrandController::class);
    Route::get('brands/active/list', [BrandController::class, 'active']);
    Route::get('brands/slug/{slug}', [BrandController::class, 'bySlug']);
    
    // =============================================
    // AUTENTICACIÓN
    // =============================================
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        
        // Rutas protegidas de autenticación
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });
    });
});

// =============================================
// RUTAS PROTEGIDAS (requieren autenticación)
// =============================================
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // =============================================
    // CARRITO
    // =============================================
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('add', [CartController::class, 'addItem']);
        Route::put('update/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('remove/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('clear', [CartController::class, 'clear']);
        Route::get('count', [CartController::class, 'count']);
        Route::get('total', [CartController::class, 'total']);
    });
    
    // =============================================
    // PEDIDOS (para futuro)
    // =============================================
    // Route::apiResource('orders', OrderController::class);
    
    // =============================================
    // FAVORITOS (para futuro)
    // =============================================
    // Route::prefix('favorites')->group(function () {
    //     Route::get('/', [FavoriteController::class, 'index']);
    //     Route::post('toggle/{productId}', [FavoriteController::class, 'toggle']);
    // });
});

// =============================================
// RUTAS DE ADMINISTRACIÓN (requieren roles admin)
// =============================================
Route::middleware(['auth:sanctum', 'admin'])->prefix('v1/admin')->group(function () {
    // Aquí irían las rutas administrativas que requieren permisos especiales
    // Por ejemplo: gestión avanzada de productos, reportes, etc.
});

// =============================================
// RUTA DE SALUD DE LA API
// =============================================
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});