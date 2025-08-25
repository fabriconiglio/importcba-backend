<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingMethod;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippingMethods = [
            [
                'name' => 'Envío Estándar',
                'description' => 'Envío estándar a todo el país (5-7 días hábiles)',
                'cost' => 3500.00,
                'estimated_days' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Envío Express',
                'description' => 'Envío express CABA y GBA (2-3 días hábiles)',
                'cost' => 5500.00,
                'estimated_days' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Envío Gratis',
                'description' => 'Envío gratis en compras superiores a $100.000',
                'cost' => 0.00,
                'estimated_days' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Retiro en Sucursal',
                'description' => 'Retirá tu pedido en nuestra sucursal (sin costo)',
                'cost' => 0.00,
                'estimated_days' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Moto Express',
                'description' => 'Entrega el mismo día en CABA (solo productos en stock)',
                'cost' => 7500.00,
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'OCA Estándar',
                'description' => 'Envío a través de OCA a todo el país',
                'cost' => 4200.00,
                'estimated_days' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Andreani Estándar',
                'description' => 'Envío a través de Andreani a todo el país',
                'cost' => 4000.00,
                'estimated_days' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($shippingMethods as $method) {
            ShippingMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }

        $this->command->info('Métodos de envío creados correctamente');
    }
}
