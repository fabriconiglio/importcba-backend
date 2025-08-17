<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductAnalytics extends BaseWidget
{

    protected function getStats(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Productos activos
        $activeProducts = Product::where('is_active', true)->count();
        $inactiveProducts = Product::where('is_active', false)->count();

        // Productos con imágenes
        $productsWithImages = Product::whereHas('images')->count();
        $productsWithoutImages = Product::whereDoesntHave('images')->count();

        // Productos por categoría
        $productsByCategory = Product::join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(products.id) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // Productos más vendidos del mes
        $topSellingProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereDate('orders.created_at', '>=', $thisMonth)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(3)
            ->get();

        // Productos con mejor margen (ejemplo)
        $highMarginProducts = Product::where('price', '>', 0)
            ->orderBy('price', 'desc')
            ->limit(3)
            ->get();

        // Productos recientes
        $recentProducts = Product::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        return [
            Stat::make('Productos Activos', $activeProducts)
                ->description('Disponibles para venta')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Productos Inactivos', $inactiveProducts)
                ->description('Pausados temporalmente')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('warning'),

            Stat::make('Con Imágenes', $productsWithImages)
                ->description('Productos con galería')
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),

            Stat::make('Sin Imágenes', $productsWithoutImages)
                ->description('Requieren fotos')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Productos Nuevos', $recentProducts)
                ->description('Últimos 30 días')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),

            Stat::make('Top Vendedor', $topSellingProducts->first()?->name ?? 'N/A')
                ->description(
                    $topSellingProducts->isNotEmpty() 
                        ? "Vendidos: " . number_format($topSellingProducts->first()?->total_sold ?? 0)
                        : "Sin ventas este mes"
                )
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
} 