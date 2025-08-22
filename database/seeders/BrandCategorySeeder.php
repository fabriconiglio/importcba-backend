<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Category;

class BrandCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapeo lógico de marcas por categorías
        $brandCategoryMappings = [
            'bazar' => [
                'Bazar Oriental' => ['is_featured' => true, 'sort_order' => 1],
                'Bazar Andino' => ['is_featured' => true, 'sort_order' => 2],
                'Bazar Mediterráneo' => ['is_featured' => false, 'sort_order' => 3],
                'Bazar Artesanal' => ['is_featured' => false, 'sort_order' => 4],
            ],
            'escolar' => [
                'Escolar Premium' => ['is_featured' => true, 'sort_order' => 1],
                'Escolar Básico' => ['is_featured' => true, 'sort_order' => 2],
                'Escolar Artístico' => ['is_featured' => false, 'sort_order' => 3],
                'Escolar Tecnológico' => ['is_featured' => false, 'sort_order' => 4],
            ],
            'hogar-y-deco' => [
                'Hogar Elegante' => ['is_featured' => true, 'sort_order' => 1],
                'Hogar Minimalista' => ['is_featured' => true, 'sort_order' => 2],
                'Deco Vintage' => ['is_featured' => false, 'sort_order' => 3],
                'Deco Rústico' => ['is_featured' => false, 'sort_order' => 4],
            ],
            'juguetes' => [
                'Juguetes Educativos' => ['is_featured' => true, 'sort_order' => 1],
                'Juguetes Clásicos' => ['is_featured' => true, 'sort_order' => 2],
                'Juguetes Artesanales' => ['is_featured' => false, 'sort_order' => 3],
            ],
            'regaleria' => [
                'Bazar Artesanal' => ['is_featured' => true, 'sort_order' => 1],
                'Deco Vintage' => ['is_featured' => true, 'sort_order' => 2],
                'Juguetes Artesanales' => ['is_featured' => false, 'sort_order' => 3],
            ],
            'ofertas' => [
                'Escolar Básico' => ['is_featured' => true, 'sort_order' => 1],
                'Bazar Oriental' => ['is_featured' => true, 'sort_order' => 2],
                'Juguetes Clásicos' => ['is_featured' => false, 'sort_order' => 3],
            ],
            'liquidacion' => [
                'Escolar Básico' => ['is_featured' => true, 'sort_order' => 1],
                'Deco Rústico' => ['is_featured' => true, 'sort_order' => 2],
                'Bazar Mediterráneo' => ['is_featured' => false, 'sort_order' => 3],
            ],
            'rigolleau' => [
                'Bazar Oriental' => ['is_featured' => true, 'sort_order' => 1],
                'Hogar Elegante' => ['is_featured' => true, 'sort_order' => 2],
            ],
            'lumilagro' => [
                'Hogar Minimalista' => ['is_featured' => true, 'sort_order' => 1],
                'Deco Vintage' => ['is_featured' => true, 'sort_order' => 2],
            ],
        ];

        foreach ($brandCategoryMappings as $categorySlug => $brands) {
            $category = Category::where('slug', $categorySlug)->first();
            
            if (!$category) {
                $this->command->warn("Categoría '{$categorySlug}' no encontrada");
                continue;
            }

            foreach ($brands as $brandName => $pivotData) {
                $brand = Brand::where('name', $brandName)->first();
                
                if (!$brand) {
                    $this->command->warn("Marca '{$brandName}' no encontrada");
                    continue;
                }

                // Verificar si la relación ya existe
                if (!$category->brands()->where('brand_id', $brand->id)->exists()) {
                    $category->brands()->attach($brand->id, $pivotData);
                    $this->command->info("Relacionada marca '{$brandName}' con categoría '{$categorySlug}'");
                }
            }
        }

        $this->command->info('Seeder de relaciones marca-categoría completado');
    }
}
