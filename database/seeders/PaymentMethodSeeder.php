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
                'is_active' => true,
                'configuration' => [
                    'processor' => 'stripe',
                    'supported_cards' => ['visa', 'mastercard', 'amex'],
                    'requires_cvv' => true,
                ],
            ],
            [
                'name' => 'Tarjeta de Débito',
                'type' => 'debit_card',
                'is_active' => true,
                'configuration' => [
                    'processor' => 'stripe',
                    'supported_cards' => ['visa', 'mastercard'],
                    'requires_cvv' => true,
                ],
            ],
            [
                'name' => 'PayPal',
                'type' => 'paypal',
                'is_active' => true,
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
                    'bank_name' => 'Banco Galicia',
                    'account_holder' => 'Import CBA Mayorista S.R.L.',
                    'document_number' => '35581839',
                    'account_number' => '0002348-3 355-9',
                    'cbu' => '0070355820000002348391',
                    'cuil' => '20355818390',
                    'alias' => 'Importcba',
                ],
            ],
            [
                'name' => 'Efectivo contra Entrega',
                'type' => 'cash_on_delivery',
                'is_active' => true,
                'configuration' => [
                    'requires_change' => true,
                    'max_amount' => 1000.00,
                ],
            ],
            [
                'name' => 'MercadoPago',
                'type' => 'mercadopago',
                'is_active' => true,
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
