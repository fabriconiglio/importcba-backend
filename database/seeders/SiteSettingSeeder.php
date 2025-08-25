<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'bank_name',
                'value' => 'Banco Santander Río',
                'type' => 'text',
                'description' => 'Nombre del banco para transferencias'
            ],
            [
                'key' => 'account_type',
                'value' => 'Cuenta Corriente',
                'type' => 'text',
                'description' => 'Tipo de cuenta bancaria'
            ],
            [
                'key' => 'account_number',
                'value' => '472-358294/7',
                'type' => 'text',
                'description' => 'Número de cuenta bancaria'
            ],
            [
                'key' => 'cbu',
                'value' => '0720472388000035829475',
                'type' => 'text',
                'description' => 'CBU para transferencias'
            ],
            [
                'key' => 'account_holder',
                'value' => 'Import CBA',
                'type' => 'text',
                'description' => 'Titular de la cuenta bancaria'
            ],
            [
                'key' => 'cuit',
                'value' => '30-71569842-3',
                'type' => 'text',
                'description' => 'CUIT del titular'
            ],
            [
                'key' => 'whatsapp_number',
                'value' => '+54 9 351 808-4713',
                'type' => 'text',
                'description' => 'Número de WhatsApp para consultas'
            ]
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Configuración del sitio creada correctamente');
    }
}
