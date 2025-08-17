<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateSampleCoupons extends Command
{
    protected $signature = 'coupons:generate-samples {--count=5 : Número de cupones a generar}';
    protected $description = 'Generar cupones de ejemplo para testing';

    public function handle()
    {
        $count = $this->option('count');
        $this->info("Generando {$count} cupones de ejemplo...");

        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Bienvenida 10%',
                'description' => '10% de descuento para nuevos clientes',
                'type' => 'percentage',
                'value' => 10,
                'minimum_amount' => 5000,
                'usage_limit' => 100,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Envío Gratis',
                'description' => 'Envío gratis en compras superiores a $15,000',
                'type' => 'fixed_amount',
                'value' => 2000,
                'minimum_amount' => 15000,
                'usage_limit' => 50,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(1),
                'is_active' => true,
            ],
            [
                'code' => 'FLASH20',
                'name' => 'Flash Sale 20%',
                'description' => '20% de descuento por tiempo limitado',
                'type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 10000,
                'usage_limit' => 200,
                'starts_at' => now(),
                'expires_at' => now()->addDays(7),
                'is_active' => true,
            ],
            [
                'code' => 'LOYALTY15',
                'name' => 'Cliente Fiel 15%',
                'description' => '15% de descuento para clientes recurrentes',
                'type' => 'percentage',
                'value' => 15,
                'minimum_amount' => 8000,
                'usage_limit' => null,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'BULK25',
                'name' => 'Compra Mayorista 25%',
                'description' => '25% de descuento en compras superiores a $25,000',
                'type' => 'percentage',
                'value' => 25,
                'minimum_amount' => 25000,
                'usage_limit' => 75,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ],
        ];

        $generated = 0;
        foreach ($coupons as $couponData) {
            if ($generated >= $count) break;

            try {
                $coupon = Coupon::create($couponData);
                $this->info("✓ Cupón creado: {$coupon->code} - {$coupon->name}");
                $generated++;
            } catch (\Exception $e) {
                $this->error("✗ Error creando cupón {$couponData['code']}: " . $e->getMessage());
            }
        }

        $this->info("\n¡Completado! Se generaron {$generated} cupones de ejemplo.");
        $this->info("Puedes verlos en el panel de administración: /admin/coupons");
    }
} 