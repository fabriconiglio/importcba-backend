<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Order;
use App\Models\User;

class Ingresos extends StatsOverviewWidget
{
    protected null|string $heading = 'Resumen de estadísticas';

    protected function getCards(): array
    {
        $mesActual = now()->month;
        $mesAnterior = now()->subMonth()->month;
        $anioActual = now()->year;
        $anioAnterior = now()->subMonth()->year;

        // Ingresos
        $ingresosActual = Order::whereYear('created_at', $anioActual)
            ->whereMonth('created_at', $mesActual)
            ->sum('total_amount');
        $ingresosAnterior = Order::whereYear('created_at', $anioAnterior)
            ->whereMonth('created_at', $mesAnterior)
            ->sum('total_amount');
        $diferenciaIngresos = $ingresosAnterior > 0 ? (($ingresosActual - $ingresosAnterior) / $ingresosAnterior) * 100 : 0;
        $tendenciaIngresos = Order::whereYear('created_at', $anioActual)
            ->orderBy('created_at')
            ->pluck('total_amount')
            ->toArray();

        // Nuevos clientes
        $clientesActual = User::whereYear('created_at', $anioActual)
            ->whereMonth('created_at', $mesActual)
            ->count();
        $clientesAnterior = User::whereYear('created_at', $anioAnterior)
            ->whereMonth('created_at', $mesAnterior)
            ->count();
        $diferenciaClientes = $clientesAnterior > 0 ? (($clientesActual - $clientesAnterior) / $clientesAnterior) * 100 : 0;
        $tendenciaClientes = User::whereYear('created_at', $anioActual)
            ->orderBy('created_at')
            ->pluck('id')
            ->toArray();

        // Nuevos pedidos
        $pedidosActual = Order::whereYear('created_at', $anioActual)
            ->whereMonth('created_at', $mesActual)
            ->count();
        $pedidosAnterior = Order::whereYear('created_at', $anioAnterior)
            ->whereMonth('created_at', $mesAnterior)
            ->count();
        $diferenciaPedidos = $pedidosAnterior > 0 ? (($pedidosActual - $pedidosAnterior) / $pedidosAnterior) * 100 : 0;
        $tendenciaPedidos = Order::whereYear('created_at', $anioActual)
            ->orderBy('created_at')
            ->pluck('id')
            ->toArray();

        return [
            Card::make('Ingresos', '$' . number_format($ingresosActual, 2, ',', '.'))
                ->description(
                    ($diferenciaIngresos >= 0 ? '+' : '') . number_format($diferenciaIngresos, 2) . '% respecto al mes anterior'
                )
                ->descriptionIcon($diferenciaIngresos >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($diferenciaIngresos >= 0 ? 'success' : 'danger')
                ->chart($tendenciaIngresos),
            Card::make('Nuevos clientes', $clientesActual)
                ->description(
                    ($diferenciaClientes >= 0 ? '+' : '') . number_format($diferenciaClientes, 2) . '% respecto al mes anterior'
                )
                ->descriptionIcon($diferenciaClientes >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($diferenciaClientes >= 0 ? 'success' : 'danger')
                ->chart(array_fill(0, count($tendenciaClientes), 1)),
            Card::make('Nuevos pedidos', $pedidosActual)
                ->description(
                    ($diferenciaPedidos >= 0 ? '+' : '') . number_format($diferenciaPedidos, 2) . '% respecto al mes anterior'
                )
                ->descriptionIcon($diferenciaPedidos >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($diferenciaPedidos >= 0 ? 'success' : 'danger')
                ->chart(array_fill(0, count($tendenciaPedidos), 1)),
        ];
    }
}
