<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Ventas por Categoría';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Ventas por categoría del mes actual
        $currentMonthSales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereDate('orders.created_at', '>=', $thisMonth)
            ->where('orders.status', '!=', 'cancelled')
            ->select('categories.name', DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_sales'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_sales', 'desc')
            ->limit(8)
            ->get();

        // Ventas por categoría del mes anterior
        $lastMonthSales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereDate('orders.created_at', '>=', $lastMonth)
            ->whereDate('orders.created_at', '<', $thisMonth)
            ->where('orders.status', '!=', 'cancelled')
            ->select('categories.name', DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_sales'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_sales', 'desc')
            ->limit(8)
            ->get();

        $labels = $currentMonthSales->pluck('name')->toArray();
        $currentData = $currentMonthSales->pluck('total_sales')->toArray();
        $lastData = [];

        // Alinear datos del mes anterior con el mes actual
        foreach ($labels as $label) {
            $lastMonthValue = $lastMonthSales->where('name', $label)->first();
            $lastData[] = $lastMonthValue ? $lastMonthValue->total_sales : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Mes Actual',
                    'data' => $currentData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Mes Anterior',
                    'data' => $lastData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Categorías',
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Ventas ($)',
                    ],
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": $" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
} 