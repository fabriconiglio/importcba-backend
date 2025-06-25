<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;
use Illuminate\Support\Carbon;

class TotalClientes extends ChartWidget
{
    protected static ?string $heading = 'Total de clientes';

    protected function getData(): array
    {
        $months = [
            'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
            'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
        ];
        $data = array_fill(0, 12, 0);
        $users = User::selectRaw('EXTRACT(MONTH FROM created_at) as mes, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('mes')
            ->pluck('total', 'mes');
        foreach ($users as $mes => $total) {
            $data[$mes - 1] = $total;
        }
        return [
            'datasets' => [
                [
                    'label' => 'Clientes',
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
