<?php

namespace App\Filament\Widgets;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CouponUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Uso de Cupones';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $days = collect();
        $usageData = collect();
        $revenueData = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days->push($date->format('d/m'));
            
            // Usos del día - datos reales
            try {
                $dailyUsage = CouponUsage::whereDate('created_at', $date)->count();
            } catch (\Exception $e) {
                $dailyUsage = 0;
            }
            $usageData->push($dailyUsage);
            
            // Ingresos del día - datos reales
            try {
                $dailyRevenue = Order::whereDate('created_at', $date)
                    ->whereNotNull('coupon_id')
                    ->sum('total_amount') ?? 0;
            } catch (\Exception $e) {
                $dailyRevenue = 0;
            }
            $revenueData->push($dailyRevenue);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cupones Usados',
                    'data' => $usageData->toArray(),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Ingresos con Cupones ($)',
                    'data' => $revenueData->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $days->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                        'text' => 'Fecha',
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Cupones Usados',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Ingresos ($)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
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
                ],
            ],
        ];
    }
} 