<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Atributo: Color
        $colorAttribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
            'is_required' => false,
        ]);

        // Valores para Color
        $colors = [
            ['value' => 'Rojo', 'color_code' => '#FF0000'],
            ['value' => 'Azul', 'color_code' => '#0000FF'],
            ['value' => 'Verde', 'color_code' => '#008000'],
            ['value' => 'Negro', 'color_code' => '#000000'],
            ['value' => 'Blanco', 'color_code' => '#FFFFFF'],
            ['value' => 'Amarillo', 'color_code' => '#FFFF00'],
            ['value' => 'Rosa', 'color_code' => '#FFC0CB'],
            ['value' => 'Gris', 'color_code' => '#808080'],
        ];

        foreach ($colors as $color) {
            AttributeValue::create([
                'attribute_id' => $colorAttribute->id,
                'value' => $color['value'],
                'color_code' => $color['color_code'],
            ]);
        }

        // 2. Atributo: Tamaño
        $sizeAttribute = Attribute::create([
            'name' => 'Tamaño',
            'slug' => 'tamano',
            'type' => 'select',
            'is_required' => false,
        ]);

        // Valores para Tamaño
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $size) {
            AttributeValue::create([
                'attribute_id' => $sizeAttribute->id,
                'value' => $size,
                'color_code' => null,
            ]);
        }

        // 3. Atributo: Material
        $materialAttribute = Attribute::create([
            'name' => 'Material',
            'slug' => 'material',
            'type' => 'select',
            'is_required' => false,
        ]);

        // Valores para Material
        $materials = [
            'Plástico',
            'Vidrio',
            'Acero Inoxidable',
            'Cerámica',
            'Silicona',
            'Aluminio',
            'Madera',
            'Bambú',
        ];

        foreach ($materials as $material) {
            AttributeValue::create([
                'attribute_id' => $materialAttribute->id,
                'value' => $material,
                'color_code' => null,
            ]);
        }

        // 4. Atributo: Capacidad
        $capacityAttribute = Attribute::create([
            'name' => 'Capacidad',
            'slug' => 'capacidad',
            'type' => 'select',
            'is_required' => false,
        ]);

        // Valores para Capacidad
        $capacities = [
            '250ml',
            '350ml',
            '400ml',
            '500ml',
            '750ml',
            '1L',
            '1.5L',
            '2L',
        ];

        foreach ($capacities as $capacity) {
            AttributeValue::create([
                'attribute_id' => $capacityAttribute->id,
                'value' => $capacity,
                'color_code' => null,
            ]);
        }

        // 5. Atributo: Estilo
        $styleAttribute = Attribute::create([
            'name' => 'Estilo',
            'slug' => 'estilo',
            'type' => 'select',
            'is_required' => false,
        ]);

        // Valores para Estilo
        $styles = [
            'Clásico',
            'Moderno',
            'Vintage',
            'Minimalista',
            'Elegante',
            'Deportivo',
            'Casual',
            'Decorativo',
        ];

        foreach ($styles as $style) {
            AttributeValue::create([
                'attribute_id' => $styleAttribute->id,
                'value' => $style,
                'color_code' => null,
            ]);
        }

        $this->command->info('✅ Se crearon 5 atributos con sus respectivos valores:');
        $this->command->info('   🎨 Color (8 valores con códigos de color)');
        $this->command->info('   📏 Tamaño (6 valores: XS-XXL)');
        $this->command->info('   🏗️  Material (8 valores: Plástico, Vidrio, etc.)');
        $this->command->info('   🥤 Capacidad (8 valores: 250ml-2L)');
        $this->command->info('   ✨ Estilo (8 valores: Clásico, Moderno, etc.)');
    }
}