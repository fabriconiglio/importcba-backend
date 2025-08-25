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
use App\Http\Controllers\API\CheckoutController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\CouponController;
use App\Http\Controllers\API\BannerController;
use App\Http\Controllers\API\AnonymousCartController;
use App\Http\Controllers\API\CartMergeController;
use App\Http\Controllers\API\StockReservationController;
use App\Http\Controllers\API\EmailController;
use App\Http\Controllers\API\DocumentationController;
use App\Http\Controllers\API\SocialAuthController;
use App\Http\Controllers\API\NewsletterController;

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
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/{id}/images', [ProductController::class, 'images']);

    // =============================================
    // CATÁLOGO SEO-FRIENDLY
    // =============================================
    Route::prefix('catalog')->group(function () {
        Route::get('/', [CatalogController::class, 'index']); // Catálogo general
        Route::get('category/{categorySlug}', [CatalogController::class, 'byCategory']);
        Route::get('brand/{brandSlug}', [CatalogController::class, 'byBrand']);
        Route::get('category/{categorySlug}/brand/{brandSlug}', [CatalogController::class, 'byCategoryAndBrand']);
        Route::get('category/{categorySlug}/subcategory/{subcategorySlug}', [CatalogController::class, 'bySubcategory']);
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
    Route::get('categories/slug/{slug}', [CategoryController::class, 'bySlug']);
    
    // =============================================
    // MARCAS
    // =============================================
    Route::apiResource('brands', BrandController::class);
    Route::get('brands/active/list', [BrandController::class, 'active']);
    Route::get('brands/slug/{slug}', [BrandController::class, 'bySlug']);
    
    // =============================================
    // MÉTODOS DE ENVÍO Y PAGO (PÚBLICOS)
    // =============================================
    Route::get('shipping-methods', [CheckoutController::class, 'shippingMethods']);
    Route::get('payment-methods', [CheckoutController::class, 'paymentMethods']);
    
    // =============================================
    // CUPONES PÚBLICOS
    // =============================================
    Route::get('coupons/public', [CouponController::class, 'publicIndex']);
    
    // =============================================
    // BANNERS PÚBLICOS
    // =============================================
    Route::get('banners/public', [BannerController::class, 'publicIndex']);
    
    // =============================================
    // NEWSLETTER PÚBLICO
    // =============================================
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);
    
    // =============================================
    // AUTENTICACIÓN
    // =============================================
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('token.rate.limit');
        Route::post('register', [AuthController::class, 'register'])->middleware('token.rate.limit');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('token.rate.limit');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('token.rate.limit');
        
        // Rutas de autenticación social
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
        Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
        
        // Rutas protegidas de autenticación
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('{provider}/disconnect', [SocialAuthController::class, 'disconnectProvider']);
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
    // CHECKOUT
    // =============================================
    Route::prefix('checkout')->group(function () {
        Route::get('initiate', [CheckoutController::class, 'initiate']);
        Route::post('calculate', [CheckoutController::class, 'calculate']);
        Route::post('confirm', [CheckoutController::class, 'confirm']);
        Route::post('validate-coupon', [CheckoutController::class, 'validateCoupon']);
    });
    
    // =============================================
    // PEDIDOS
    // =============================================
    Route::apiResource('orders', OrderController::class);
    Route::get('orders/status/{status}', [OrderController::class, 'byStatus']);
    Route::get('orders/stats', [OrderController::class, 'stats']);
    
    // =============================================
    // PAGOS
    // =============================================
    Route::prefix('payments')->group(function () {
        Route::post('process', [PaymentController::class, 'processPayment']);
        Route::get('info/{paymentId}', [PaymentController::class, 'getPaymentInfo']);
        Route::post('refund/{paymentId}', [PaymentController::class, 'refundPayment']);
        Route::post('create-method', [PaymentController::class, 'createPaymentMethod']);
        Route::get('providers', [PaymentController::class, 'getProviders']);
        Route::post('validate', [PaymentController::class, 'validatePaymentData']);
        Route::get('providers/{providerName}/methods', [PaymentController::class, 'getSupportedMethods']);
    });
    
    // =============================================
    // CUPONES
    // =============================================
    Route::prefix('coupons')->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::post('validate', [CouponController::class, 'validate']);
        Route::post('apply', [CouponController::class, 'apply']);
        Route::post('remove', [CouponController::class, 'remove']);
        Route::get('history', [CouponController::class, 'history']);
    });
    
    // =============================================
    // CARRITO ANÓNIMO
    // =============================================
    Route::prefix('anonymous-cart')->group(function () {
        Route::get('/', [AnonymousCartController::class, 'index']);
        Route::post('add', [AnonymousCartController::class, 'addItem']);
        Route::put('items/{itemId}', [AnonymousCartController::class, 'updateItem']);
        Route::delete('items/{itemId}', [AnonymousCartController::class, 'removeItem']);
        Route::delete('clear', [AnonymousCartController::class, 'clear']);
        Route::get('count', [AnonymousCartController::class, 'count']);
        Route::get('total', [AnonymousCartController::class, 'total']);
    });
    
    // =============================================
    // MERGE DE CARRITO
    // =============================================
    Route::prefix('cart-merge')->group(function () {
        Route::post('merge', [CartMergeController::class, 'merge']);
        Route::get('anonymous-info', [CartMergeController::class, 'getAnonymousCartInfo']);
        Route::get('stats', [CartMergeController::class, 'stats']);
        Route::post('clean-expired', [CartMergeController::class, 'cleanExpired']);
    });
    
    // =============================================
    // RESERVAS DE STOCK
    // =============================================
    Route::prefix('stock-reservations')->group(function () {
        Route::post('/', [StockReservationController::class, 'create']);
        Route::post('{reservationId}/confirm', [StockReservationController::class, 'confirm']);
        Route::post('{reservationId}/cancel', [StockReservationController::class, 'cancel']);
        Route::post('{reservationId}/extend', [StockReservationController::class, 'extendExpiration']);
        Route::post('order/{orderId}/reserve', [StockReservationController::class, 'reserveForOrder']);
        Route::post('order/{orderId}/confirm', [StockReservationController::class, 'confirmOrderReservations']);
        Route::post('order/{orderId}/cancel', [StockReservationController::class, 'cancelOrderReservations']);
        Route::get('product/{productId}/available', [StockReservationController::class, 'getAvailableStock']);
        Route::post('check-availability', [StockReservationController::class, 'checkAvailability']);
        Route::get('product/{productId}/reservations', [StockReservationController::class, 'getProductReservations']);
        Route::get('stats', [StockReservationController::class, 'getStats']);
        Route::post('clean-expired', [StockReservationController::class, 'cleanExpired']);
    });
    
    // =============================================
    // EMAILS
    // =============================================
    Route::prefix('emails')->group(function () {
        Route::post('order/{orderId}/confirmation', [EmailController::class, 'sendOrderConfirmation']);
        Route::post('order/{orderId}/confirmation/queue', [EmailController::class, 'queueOrderConfirmation']);
        Route::post('password-reset', [EmailController::class, 'sendPasswordReset']);
        Route::post('password-reset/queue', [EmailController::class, 'queuePasswordReset']);
        Route::post('welcome/{userId}', [EmailController::class, 'sendWelcome']);
        Route::post('welcome/{userId}/queue', [EmailController::class, 'queueWelcome']);
        Route::get('check-configuration', [EmailController::class, 'checkConfiguration']);
        Route::get('stats', [EmailController::class, 'getStats']);
        Route::post('test', [EmailController::class, 'sendTestEmail']);
    });
    
    // =============================================
    // DOCUMENTACIÓN
    // =============================================
    Route::prefix('docs')->group(function () {
        Route::get('/', [DocumentationController::class, 'index']);
        Route::get('health', [DocumentationController::class, 'health']);
        Route::get('endpoints', [DocumentationController::class, 'endpoints']);
    });
    
    // =============================================
    // NEWSLETTER (ADMIN)
    // =============================================
    Route::get('newsletter/stats', [NewsletterController::class, 'stats']);
    
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