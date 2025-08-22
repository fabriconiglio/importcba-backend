<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Banner;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'title' => 'MEGA DESCUENTO',
                'description' => 'En utensilios de importadora',
                'image_url' => 'banners/banner-mega-descuento.jpg',
                'link_url' => '/catalogo?featured=true',
                'link_text' => 'Ver Ofertas',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'MEGA SALE',
                'description' => 'Hasta 25% OFF en productos seleccionados',
                'image_url' => 'banners/banner-mega-sale.jpg',
                'link_url' => '/descuentos',
                'link_text' => 'Ver Descuentos',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Envío Gratis',
                'description' => 'En compras superiores a $15.000',
                'image_url' => 'banners/banner-envio-gratis.jpg',
                'link_url' => '/envios',
                'link_text' => 'Más Información',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $bannerData) {
            Banner::firstOrCreate(
                ['title' => $bannerData['title']],
                $bannerData
            );
        }

        $this->command->info('Banners creados correctamente');
    }
}
