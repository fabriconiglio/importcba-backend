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
                'description' => 'Envío estándar de 3-5 días hábiles',
                'cost' => 5.99,
                'estimated_days' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Envío Express',
                'description' => 'Envío express de 1-2 días hábiles',
                'cost' => 12.99,
                'estimated_days' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Envío Gratis',
                'description' => 'Envío gratis en pedidos superiores a $50',
                'cost' => 0.00,
                'estimated_days' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Recogida en Tienda',
                'description' => 'Recoge tu pedido en nuestra tienda',
                'cost' => 0.00,
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Envío Premium',
                'description' => 'Envío premium con seguimiento en tiempo real',
                'cost' => 19.99,
                'estimated_days' => 1,
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
