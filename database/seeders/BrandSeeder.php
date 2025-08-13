<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Apple',
                'description' => 'Tecnología innovadora y diseño premium',
                'logo_url' => 'https://via.placeholder.com/150x50/000000/FFFFFF?text=Apple',
                'is_active' => true,
            ],
            [
                'name' => 'Samsung',
                'description' => 'Electrónicos de alta calidad y innovación',
                'logo_url' => 'https://via.placeholder.com/150x50/1428A0/FFFFFF?text=Samsung',
                'is_active' => true,
            ],
            [
                'name' => 'Sony',
                'description' => 'Audio, video y entretenimiento de calidad',
                'logo_url' => 'https://via.placeholder.com/150x50/000000/FFFFFF?text=Sony',
                'is_active' => true,
            ],
            [
                'name' => 'LG',
                'description' => 'Electrodomésticos y tecnología del hogar',
                'logo_url' => 'https://via.placeholder.com/150x50/A50034/FFFFFF?text=LG',
                'is_active' => true,
            ],
            [
                'name' => 'Nike',
                'description' => 'Calzado y ropa deportiva de alto rendimiento',
                'logo_url' => 'https://via.placeholder.com/150x50/000000/FFFFFF?text=Nike',
                'is_active' => true,
            ],
            [
                'name' => 'Adidas',
                'description' => 'Ropa y calzado deportivo innovador',
                'logo_url' => 'https://via.placeholder.com/150x50/000000/FFFFFF?text=Adidas',
                'is_active' => true,
            ],
            [
                'name' => 'Coca-Cola',
                'description' => 'Bebidas refrescantes y productos de consumo',
                'logo_url' => 'https://via.placeholder.com/150x50/F40009/FFFFFF?text=Coca-Cola',
                'is_active' => true,
            ],
            [
                'name' => 'Pepsi',
                'description' => 'Bebidas carbonatadas y snacks',
                'logo_url' => 'https://via.placeholder.com/150x50/004B93/FFFFFF?text=Pepsi',
                'is_active' => true,
            ],
            [
                'name' => 'Toyota',
                'description' => 'Vehículos confiables y eficientes',
                'logo_url' => 'https://via.placeholder.com/150x50/000000/FFFFFF?text=Toyota',
                'is_active' => true,
            ],
            [
                'name' => 'Honda',
                'description' => 'Automóviles y motocicletas de calidad',
                'logo_url' => 'https://via.placeholder.com/150x50/000000/FFFFFF?text=Honda',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::firstOrCreate(
                ['name' => $brandData['name']],
                $brandData
            );
        }

        $this->command->info('Marcas creadas correctamente');
    }
}
