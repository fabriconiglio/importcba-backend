<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Tarjeta de Crédito',
                'type' => 'credit_card',
                'is_active' => false,
                'configuration' => [
                    'processor' => 'stripe',
                    'supported_cards' => ['visa', 'mastercard', 'amex'],
                    'requires_cvv' => true,
                ],
            ],
            [
                'name' => 'Tarjeta de Débito',
                'type' => 'debit_card',
                'is_active' => false,
                'configuration' => [
                    'processor' => 'stripe',
                    'supported_cards' => ['visa', 'mastercard'],
                    'requires_cvv' => true,
                ],
            ],
            [
                'name' => 'PayPal',
                'type' => 'paypal',
                'is_active' => false,
                'configuration' => [
                    'processor' => 'paypal',
                    'environment' => 'sandbox',
                ],
            ],
            [
                'name' => 'Transferencia Bancaria',
                'type' => 'bank_transfer',
                'is_active' => true,
                'configuration' => [
                    'bank_name' => 'Banco Santander Río',
                    'account_holder' => 'Import CBA',
                    'account_number' => '472-358294/7',
                    'cbu' => '0720472388000035829475',
                    'cuit' => '30-71569842-3',
                ],
            ],
            [
                'name' => 'Efectivo contra Entrega',
                'type' => 'cash_on_delivery',
                'is_active' => false,
                'configuration' => [
                    'requires_change' => true,
                    'max_amount' => 1000.00,
                ],
            ],
            [
                'name' => 'MercadoPago',
                'type' => 'mercadopago',
                'is_active' => false,
                'configuration' => [
                    'processor' => 'mercadopago',
                    'environment' => 'sandbox',
                ],
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }

        $this->command->info('Métodos de pago creados correctamente');
    }
}
