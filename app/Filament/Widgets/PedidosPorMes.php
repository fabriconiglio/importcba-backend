<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Carbon;

class PedidosPorMes extends ChartWidget
{
    protected static ?string $heading = 'Pedidos por mes';

    protected function getData(): array
    {
        $months = [
            'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
            'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
        ];
        $data = array_fill(0, 12, 0);
        $orders = Order::selectRaw('EXTRACT(MONTH FROM created_at) as mes, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('mes')
            ->pluck('total', 'mes');
        foreach ($orders as $mes => $total) {
            $data[$mes - 1] = $total;
        }
        return [
            'datasets' => [
                [
                    'label' => 'Pedidos',
                    'data' => $data,
                    'backgroundColor' => '#fbbf24',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
