<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'DESCUENTO10',
                'name' => 'Descuento 10%',
                'description' => '10% de descuento en toda la compra',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 50.00,
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'DESCUENTO20',
                'name' => 'Descuento 20%',
                'description' => '20% de descuento en compras superiores a $100',
                'type' => 'percentage',
                'value' => 20.00,
                'minimum_amount' => 100.00,
                'usage_limit' => 50,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
            ],
            [
                'code' => 'ENVIOGRATIS',
                'name' => 'Envío Gratis',
                'description' => 'Envío gratis en toda la compra',
                'type' => 'fixed_amount',
                'value' => 5.99,
                'minimum_amount' => 25.00,
                'usage_limit' => 200,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
            ],
            [
                'code' => 'BIENVENIDA',
                'name' => 'Cupón de Bienvenida',
                'description' => '$15 de descuento para nuevos clientes',
                'type' => 'fixed_amount',
                'value' => 15.00,
                'minimum_amount' => 30.00,
                'usage_limit' => 1000,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ],
            [
                'code' => 'BLACKFRIDAY',
                'name' => 'Black Friday',
                'description' => '30% de descuento en Black Friday',
                'type' => 'percentage',
                'value' => 30.00,
                'minimum_amount' => 75.00,
                'usage_limit' => 500,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now()->addDays(30),
                'expires_at' => now()->addDays(35),
            ],
            [
                'code' => 'NAVIDAD',
                'name' => 'Cupón de Navidad',
                'description' => '25% de descuento en compras navideñas',
                'type' => 'percentage',
                'value' => 25.00,
                'minimum_amount' => 60.00,
                'usage_limit' => 300,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now()->addDays(60),
                'expires_at' => now()->addDays(90),
            ],
            [
                'code' => 'FLASH',
                'name' => 'Oferta Flash',
                'description' => '$25 de descuento por tiempo limitado',
                'type' => 'fixed_amount',
                'value' => 25.00,
                'minimum_amount' => 100.00,
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(7),
            ],
            [
                'code' => 'EXPIRADO',
                'name' => 'Cupón Expirado',
                'description' => 'Cupón para pruebas de expiración',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 20.00,
                'usage_limit' => 50,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now()->subDays(30),
                'expires_at' => now()->subDays(1),
            ],
            [
                'code' => 'FUTURO',
                'name' => 'Cupón Futuro',
                'description' => 'Cupón que aún no está disponible',
                'type' => 'percentage',
                'value' => 15.00,
                'minimum_amount' => 40.00,
                'usage_limit' => 75,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now()->addDays(15),
                'expires_at' => now()->addDays(45),
            ],
            [
                'code' => 'SINLIMITE',
                'name' => 'Sin Límite de Uso',
                'description' => 'Cupón sin límite de uso global',
                'type' => 'fixed_amount',
                'value' => 10.00,
                'minimum_amount' => 25.00,
                'usage_limit' => null,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(12),
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::firstOrCreate(
                ['code' => $couponData['code']],
                $couponData
            );
        }

        $this->command->info('Cupones creados correctamente');
    }
}
