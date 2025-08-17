<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LowStockAlert extends BaseWidget
{
    protected function getStats(): array
    {
        // Productos con stock bajo (menos de 10 unidades)
        $lowStockProducts = Product::where('stock_quantity', '<', 10)
            ->where('stock_quantity', '>', 0)
            ->count();

        // Productos sin stock
        $outOfStockProducts = Product::where('stock_quantity', '<=', 0)->count();

        // Productos críticos (menos de 5 unidades)
        $criticalStockProducts = Product::where('stock_quantity', '<', 5)
            ->where('stock_quantity', '>', 0)
            ->count();

        // Valor total del stock bajo
        $lowStockValue = Product::where('stock_quantity', '<', 10)
            ->where('stock_quantity', '>', 0)
            ->sum(DB::raw('stock_quantity * price'));

        // Productos que necesitan reposición urgente
        $urgentRestock = Product::where('stock_quantity', '<', 3)
            ->where('stock_quantity', '>', 0)
            ->count();

        // Productos con stock alto (más de 100 unidades)
        $highStockProducts = Product::where('stock_quantity', '>', 100)->count();

        return [
            Stat::make('Stock Bajo', $lowStockProducts)
                ->description('Menos de 10 unidades')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->chart($this->getLowStockTrend()),

            Stat::make('Sin Stock', $outOfStockProducts)
                ->description('Requieren reposición inmediata')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Stock Crítico', $criticalStockProducts)
                ->description('Menos de 5 unidades')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Valor Stock Bajo', '$' . number_format($lowStockValue, 0))
                ->description('Valor total en inventario')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('Reposición Urgente', $urgentRestock)
                ->description('Menos de 3 unidades')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Stock Alto', $highStockProducts)
                ->description('Más de 100 unidades')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    private function getLowStockTrend(): array
    {
        // Simular tendencia de stock bajo (en producción esto podría venir de logs históricos)
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Product::where('stock_quantity', '<', 10)
                ->where('stock_quantity', '>', 0)
                ->whereDate('updated_at', '<=', $date)
                ->count();
            $trend[] = $count;
        }
        return $trend;
    }
} 