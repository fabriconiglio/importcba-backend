<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrdersOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Pedidos de hoy
        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::whereDate('created_at', $today)->sum('total_amount');

        // Pedidos del mes actual
        $monthOrders = Order::whereDate('created_at', '>=', $thisMonth)->count();
        $monthRevenue = Order::whereDate('created_at', '>=', $thisMonth)->sum('total_amount');

        // Pedidos del mes anterior
        $lastMonthOrders = Order::whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<', $thisMonth)
            ->count();
        $lastMonthRevenue = Order::whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<', $thisMonth)
            ->sum('total_amount');

        // Cálculo de crecimiento
        $orderGrowth = $lastMonthOrders > 0 
            ? (($monthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 
            : 0;
        
        $revenueGrowth = $lastMonthRevenue > 0 
            ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 0;

        // Estados de pedidos
        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $shippedOrders = Order::where('status', 'shipped')->count();

        // Estados de pago
        $paidOrders = Order::where('payment_status', 'paid')->count();
        $pendingPayments = Order::where('payment_status', 'pending')->count();

        return [
            Stat::make('Pedidos Hoy', $todayOrders)
                ->description("Ingresos: $" . number_format($todayRevenue, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Pedidos del Mes', $monthOrders)
                ->description("Ingresos: $" . number_format($monthRevenue, 2))
                ->descriptionIcon($orderGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($orderGrowth >= 0 ? 'success' : 'danger')
                ->color('primary'),

            Stat::make('Crecimiento Mensual', number_format($orderGrowth, 1) . '%')
                ->description("vs mes anterior")
                ->descriptionIcon($orderGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($orderGrowth >= 0 ? 'success' : 'danger')
                ->color($orderGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Pedidos Pendientes', $pendingOrders)
                ->description("Requieren atención")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Pedidos Procesando', $processingOrders)
                ->description("En preparación")
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),

            Stat::make('Pedidos Enviados', $shippedOrders)
                ->description("En tránsito")
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),

            Stat::make('Pagos Confirmados', $paidOrders)
                ->description("Ingresos asegurados")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pagos Pendientes', $pendingPayments)
                ->description("Requieren confirmación")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
} 