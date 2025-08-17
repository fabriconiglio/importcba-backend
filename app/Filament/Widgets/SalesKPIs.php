<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesKPIs extends BaseWidget
{

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Ventas del día
        $todaySales = Order::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $todayOrders = Order::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        // Ventas del mes actual
        $monthSales = Order::whereDate('created_at', '>=', $thisMonth)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $monthOrders = Order::whereDate('created_at', '>=', $thisMonth)
            ->where('status', '!=', 'cancelled')
            ->count();

        // Ventas del mes anterior
        $lastMonthSales = Order::whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<', $thisMonth)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $lastMonthOrders = Order::whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<', $thisMonth)
            ->where('status', '!=', 'cancelled')
            ->count();

        // Cálculo de crecimiento
        $salesGrowth = $lastMonthSales > 0 
            ? (($monthSales - $lastMonthSales) / $lastMonthSales) * 100 
            : 0;
        
        $ordersGrowth = $lastMonthOrders > 0 
            ? (($monthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 
            : 0;

        // Ticket promedio
        $avgTicket = $monthOrders > 0 ? $monthSales / $monthOrders : 0;
        $lastMonthAvgTicket = $lastMonthOrders > 0 ? $lastMonthSales / $lastMonthOrders : 0;
        $ticketGrowth = $lastMonthAvgTicket > 0 
            ? (($avgTicket - $lastMonthAvgTicket) / $lastMonthAvgTicket) * 100 
            : 0;

        // Productos más vendidos
        $topProducts = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereDate('orders.created_at', '>=', $thisMonth)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(3)
            ->get();

        return [
            Stat::make('Ventas del Mes', '$' . number_format($monthSales, 0))
                ->description(
                    $salesGrowth >= 0 
                        ? "+" . number_format($salesGrowth, 1) . "% vs mes anterior"
                        : number_format($salesGrowth, 1) . "% vs mes anterior"
                )
                ->descriptionIcon($salesGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($salesGrowth >= 0 ? 'success' : 'danger')
                ->color($salesGrowth >= 0 ? 'success' : 'danger')
                ->chart([$lastMonthSales, $monthSales]),

            Stat::make('Pedidos del Mes', number_format($monthOrders))
                ->description(
                    $ordersGrowth >= 0 
                        ? "+" . number_format($ordersGrowth, 1) . "% vs mes anterior"
                        : number_format($ordersGrowth, 1) . "% vs mes anterior"
                )
                ->descriptionIcon($ordersGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($ordersGrowth >= 0 ? 'success' : 'danger')
                ->color('primary'),

            Stat::make('Ticket Promedio', '$' . number_format($avgTicket, 0))
                ->description(
                    $ticketGrowth >= 0 
                        ? "+" . number_format($ticketGrowth, 1) . "% vs mes anterior"
                        : number_format($ticketGrowth, 1) . "% vs mes anterior"
                )
                ->descriptionIcon($ticketGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($ticketGrowth >= 0 ? 'success' : 'danger')
                ->color('info'),

            Stat::make('Ventas de Hoy', '$' . number_format($todaySales, 0))
                ->description("{$todayOrders} pedidos")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Productos Top', $topProducts->first()?->name ?? 'N/A')
                ->description(
                    $topProducts->isNotEmpty() 
                        ? "Vendidos: " . number_format($topProducts->first()?->total_sold ?? 0)
                        : "Sin ventas este mes"
                )
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Tasa de Conversión', $this->calculateConversionRate())
                ->description('Pedidos / Visitas (estimado)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }

    private function calculateConversionRate(): string
    {
        // Estimación basada en pedidos del mes
        $monthOrders = Order::whereDate('created_at', '>=', Carbon::now()->startOfMonth())
            ->where('status', '!=', 'cancelled')
            ->count();
        
        // Estimación de visitas (ejemplo: 100 visitas por pedido)
        $estimatedVisits = $monthOrders * 100;
        
        if ($estimatedVisits > 0) {
            $rate = ($monthOrders / $estimatedVisits) * 100;
            return number_format($rate, 2) . '%';
        }
        
        return '0%';
    }
} 