<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        
        // Obtener todas las marcas y categorías
        $brands = Brand::all();
        $categories = Category::whereNotNull('parent_id')->get(); // Solo categorías hijas
        $attributes = Attribute::with('attributeValues')->get();

        if ($brands->isEmpty() || $categories->isEmpty()) {
            $this->command->error('❌ No se encontraron marcas o categorías. Ejecuta primero BrandSeeder y CategorySeeder');
            return;
        }

        $this->command->info('🛍️ Creando productos variados...');

        // Productos predefinidos
        $this->createPredefinedProducts($faker, $brands, $categories, $attributes);
        
        // Productos aleatorios adicionales
        $this->createRandomProducts($faker, $brands, $categories, $attributes, 25);

        $totalProducts = Product::count();
        $this->command->info("✅ Se crearon {$totalProducts} productos en total");
    }

    private function createPredefinedProducts($faker, $brands, $categories, $attributes)
    {
        $products = [
            // Vajilla
            ['name' => 'Juego de Platos Modernos x6', 'category' => 'Platos', 'price' => 12500],
            ['name' => 'Copa de Vino Elegante x4', 'category' => 'Vasos y Copas', 'price' => 8900],
            ['name' => 'Set Cubiertos Acero x24', 'category' => 'Cubiertos', 'price' => 15600],
            ['name' => 'Bandeja Servir Bambú', 'category' => 'Bandejas', 'price' => 6800],
            
            // Térmicos
            ['name' => 'Termo Stanley 1L Verde', 'category' => 'Termos', 'price' => 18900],
            ['name' => 'Conservadora Rígida 20L', 'category' => 'Conservadoras', 'price' => 24500],
            ['name' => 'Vianda Térmica Doble', 'category' => 'Viandas Térmicas', 'price' => 9800],
            
            // Utensilios
            ['name' => 'Set Espátulas Silicona x5', 'category' => 'Utensilios', 'price' => 7200],
            ['name' => 'Sartén Antiadherente 28cm', 'category' => 'Ollas y Sartenes', 'price' => 16800],
            ['name' => 'Olla Acero Inoxidable 5L', 'category' => 'Ollas y Sartenes', 'price' => 22400],
            
            // Escolares
            ['name' => 'Cuaderno Universitario A4 x5', 'category' => 'Cuadernos', 'price' => 4500],
            ['name' => 'Set Lapiceras Gel x12', 'category' => 'Útiles de Escritura', 'price' => 3200],
            ['name' => 'Cartuchera Triple Cremallera', 'category' => 'Cartucheras', 'price' => 5800],
            
            // Hogar
            ['name' => 'Organizador Cajón Plástico', 'category' => 'Organizadores', 'price' => 3800],
            ['name' => 'Lámpara Mesa LED', 'category' => 'Iluminación', 'price' => 15600],
            ['name' => 'Set Limpieza Completo', 'category' => 'Limpieza', 'price' => 18700],
        ];

        foreach ($products as $productData) {
            $this->createSingleProduct($faker, $brands, $categories, $attributes, $productData);
        }
    }

    private function createSingleProduct($faker, $brands, $categories, $attributes, $productData)
    {
        // Buscar categoría
        $category = $categories->where('name', $productData['category'])->first() ?? $categories->random();
        
        // Seleccionar marca apropiada
        $brand = $this->selectAppropiateBrand($brands, $productData['category']);

        $basePrice = $productData['price'];
        $salePrice = $faker->boolean(30) ? round($basePrice * 0.85, -2) : null;

        $product = Product::create([
            'name' => $productData['name'],
            'slug' => Str::slug($productData['name']) . '-' . Str::random(4),
            'sku' => 'SKU-' . strtoupper(Str::random(8)),
            'description' => "Producto de alta calidad. {$faker->sentence(10)}",
            'short_description' => "Excelente producto para el hogar",
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => $basePrice,
            'sale_price' => $salePrice,
            'cost_price' => round($basePrice * 0.6, -2),
            'stock_quantity' => $faker->numberBetween(5, 100),
            'min_stock_level' => 10,
            'weight' => $faker->randomFloat(3, 0.1, 5.0),
            'dimensions' => json_encode([
                'length' => $faker->numberBetween(10, 50),
                'width' => $faker->numberBetween(10, 40),
                'height' => $faker->numberBetween(5, 30),
            ]),
            'is_active' => true,
            'is_featured' => $faker->boolean(25),
            'meta_title' => $productData['name'] . ' - Import Mayorista',
            'meta_description' => 'Compra ' . $productData['name'],
        ]);

        // Asignar atributos
        $this->assignProductAttributes($product, $attributes);

        $this->command->line("   ✅ {$product->name} - {$brand->name}");
        return $product;
    }

    private function selectAppropiateBrand($brands, $category)
    {
        $categoryBrandMap = [
            'Termos' => ['Stanley', 'Thermos'],
            'Platos' => ['Corelle', 'Luminarc'],
            'Cubiertos' => ['Tramontina'],
            'Utensilios' => ['Tramontina', 'Oxo'],
            'Útiles de Escritura' => ['Faber-Castell', 'Staedtler'],
        ];

        if (isset($categoryBrandMap[$category])) {
            $preferred = $brands->whereIn('name', $categoryBrandMap[$category]);
            if ($preferred->isNotEmpty()) {
                return $preferred->random();
            }
        }

        return $brands->random();
    }

    private function assignProductAttributes($product, $attributes)
    {
        foreach ($attributes->take(2) as $attribute) { // Solo 2 atributos por producto
            if ($attribute->attributeValues->isNotEmpty() && mt_rand(1, 100) <= 50) {
                $randomValue = $attribute->attributeValues->random();
                
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'attribute_id' => $attribute->id,
                    'attribute_value_id' => $randomValue->id,
                ]);
            }
        }
    }

    private function createRandomProducts($faker, $brands, $categories, $attributes, $count)
    {
        $this->command->info("🎲 Creando {$count} productos adicionales...");

        $templates = ['Taza', 'Vaso', 'Plato', 'Bowl', 'Termo', 'Olla', 'Cuaderno', 'Lapicera'];
        $materials = ['Plástico', 'Vidrio', 'Acero', 'Cerámica'];
        $colors = ['Rojo', 'Azul', 'Verde', 'Negro', 'Blanco'];

        for ($i = 0; $i < $count; $i++) {
            $template = $faker->randomElement($templates);
            $material = $faker->randomElement($materials);
            $color = $faker->randomElement($colors);
            
            $productData = [
                'name' => "{$template} {$material} {$color}",
                'category' => 'General',
                'price' => $faker->numberBetween(1500, 15000)
            ];

            $this->createSingleProduct($faker, $brands, $categories, $attributes, $productData);
        }
    }
}