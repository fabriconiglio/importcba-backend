<?php

namespace App\Filament\Widgets;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreManagementOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Datos básicos para evitar errores
        $totalOrders = 0;
        $pendingOrders = 0;
        $activeCoupons = 0;
        $expiredCoupons = 0;
        $activeShippingMethods = 0;
        $activePaymentMethods = 0;

        try {
            $totalOrders = Order::count();
        } catch (\Exception $e) {
            // Ignorar errores
        }

        try {
            $pendingOrders = Order::where('status', 'pending')->count();
        } catch (\Exception $e) {
            // Ignorar errores
        }

        try {
            $activeCoupons = Coupon::where('is_active', true)->count();
        } catch (\Exception $e) {
            // Ignorar errores
        }

        try {
            $expiredCoupons = Coupon::where('expires_at', '<', now())->count();
        } catch (\Exception $e) {
            // Ignorar errores
        }

        try {
            $activeShippingMethods = ShippingMethod::where('is_active', true)->count();
        } catch (\Exception $e) {
            // Ignorar errores
        }

        try {
            $activePaymentMethods = PaymentMethod::where('is_active', true)->count();
        } catch (\Exception $e) {
            // Ignorar errores
        }

        return [
            Stat::make('Total de Pedidos', $totalOrders)
                ->description('Todos los pedidos del sistema')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Pedidos Pendientes', $pendingOrders)
                ->description('Requieren atención')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            Stat::make('Cupones Activos', $activeCoupons)
                ->description('Disponibles para uso')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),

            Stat::make('Cupones Expirados', $expiredCoupons)
                ->description('Necesitan renovación')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiredCoupons > 0 ? 'danger' : 'gray'),

            Stat::make('Métodos de Envío', $activeShippingMethods)
                ->description('Activos y disponibles')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Métodos de Pago', $activePaymentMethods)
                ->description('Activos y disponibles')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),
        ];
    }
} 