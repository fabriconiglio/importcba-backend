<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// MOD-026 (main): Creada plantilla Excel para importación de nuevos productos
class ProductCreateTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * Datos de ejemplo para la plantilla
     */
    public function array(): array
    {
        // Obtener algunas categorías y marcas como ejemplo
        $sampleCategories = Category::take(3)->pluck('name')->toArray();
        $sampleBrands = Brand::take(3)->pluck('name')->toArray();

        return [
            [
                'Producto Ejemplo 1', // nombre
                'PROD001', // sku
                'Descripción detallada del producto ejemplo 1. Este campo es opcional pero recomendado para SEO.', // descripcion
                'Descripción corta del producto', // descripcion_corta
                $sampleCategories[0] ?? 'Categoría Ejemplo', // categoria
                $sampleBrands[0] ?? 'Marca Ejemplo', // marca
                '1500.00', // precio
                '1200.00', // precio_oferta
                '50', // stock
                '5', // stock_minimo
                'Producto Ejemplo 1 - Título SEO', // meta_titulo
                'Meta descripción para SEO del producto ejemplo 1', // meta_descripcion
                '0.5', // peso
                '10', // largo
                '5', // ancho
                '8', // alto
                'SI', // activo
                'NO' // destacado
            ],
            [
                'Producto Ejemplo 2', // nombre
                'PROD002', // sku
                'Descripción detallada del producto ejemplo 2.', // descripcion
                'Descripción corta del segundo producto', // descripcion_corta
                $sampleCategories[1] ?? 'Otra Categoría', // categoria
                $sampleBrands[1] ?? 'Otra Marca', // marca
                '2500.00', // precio
                '', // precio_oferta (vacío = sin oferta)
                '25', // stock
                '10', // stock_minimo
                'Producto Ejemplo 2 - SEO Title', // meta_titulo
                'Meta descripción para el segundo producto de ejemplo', // meta_descripcion
                '1.2', // peso
                '15', // largo
                '8', // ancho
                '12', // alto
                'SI', // activo
                'SI' // destacado
            ],
            [
                'Producto Sin Marca', // nombre
                '', // sku (vacío = se genera automático)
                'Este producto no tiene marca asignada.', // descripcion
                '', // descripcion_corta (vacío)
                $sampleCategories[2] ?? 'Categoría General', // categoria
                '', // marca (vacío = sin marca)
                '750.00', // precio
                '', // precio_oferta
                '100', // stock
                '0', // stock_minimo
                '', // meta_titulo (vacío = se usa el nombre)
                '', // meta_descripcion (vacío = se genera automática)
                '', // peso (vacío)
                '', // largo (vacío)
                '', // ancho (vacío)
                '', // alto (vacío)
                'SI', // activo
                'NO' // destacado
            ]
        ];
    }

    /**
     * Encabezados de las columnas
     * MOD-028 (main): Cambiados a minúsculas para coincidir con el importador
     */
    public function headings(): array
    {
        return [
            'nombre', // Campo obligatorio
            'sku', // Opcional - se genera automático si está vacío
            'descripcion',
            'descripcion_corta',
            'categoria', // Debe existir en el sistema
            'marca', // Debe existir en el sistema (opcional)
            'precio', // Campo obligatorio
            'precio_oferta',
            'stock',
            'stock_minimo',
            'meta_titulo',
            'meta_descripcion',
            'peso_kg',
            'largo_cm',
            'ancho_cm',
            'alto_cm',
            'activo',
            'destacado'
        ];
    }

    /**
     * Estilos de la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para los encabezados
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563eb'] // Azul
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Estilo para las filas de datos
        $sheet->getStyle('A2:R4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Resaltar campos obligatorios
        $sheet->getStyle('A1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'dc2626'] // Rojo para campos obligatorios
            ]
        ]);

        $sheet->getStyle('G1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'dc2626'] // Rojo para campos obligatorios
            ]
        ]);

        // Agregar comentarios explicativos
        $this->addComments($sheet);

        return [];
    }

    /**
     * Anchos de las columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25, // NOMBRE
            'B' => 15, // SKU
            'C' => 40, // DESCRIPCIÓN
            'D' => 30, // DESCRIPCIÓN CORTA
            'E' => 20, // CATEGORÍA
            'F' => 15, // MARCA
            'G' => 12, // PRECIO
            'H' => 15, // PRECIO OFERTA
            'I' => 10, // STOCK
            'J' => 15, // STOCK MÍNIMO
            'K' => 25, // META TÍTULO
            'L' => 35, // META DESCRIPCIÓN
            'M' => 12, // PESO
            'N' => 12, // LARGO
            'O' => 12, // ANCHO
            'P' => 12, // ALTO
            'Q' => 10, // ACTIVO
            'R' => 12  // DESTACADO
        ];
    }

    /**
     * Agregar comentarios explicativos a las celdas
     */
    protected function addComments(Worksheet $sheet)
    {
        // Comentario para NOMBRE
        $sheet->getComment('A1')->getText()->createTextRun('Campo obligatorio. Nombre del producto que aparecerá en el catálogo.');

        // Comentario para SKU
        $sheet->getComment('B1')->getText()->createTextRun('Código único del producto. Si se deja vacío, se generará automáticamente basado en el nombre.');

        // Comentario para CATEGORÍA
        $sheet->getComment('E1')->getText()->createTextRun('Debe ser el nombre exacto de una categoría existente en el sistema. Si no existe, el producto no se creará.');

        // Comentario para MARCA
        $sheet->getComment('F1')->getText()->createTextRun('Debe ser el nombre exacto de una marca existente en el sistema. Campo opcional.');

        // Comentario para PRECIO
        $sheet->getComment('G1')->getText()->createTextRun('Campo obligatorio. Solo números, sin símbolos de moneda. Ejemplo: 1500.50');

        // Comentario para PRECIO OFERTA
        $sheet->getComment('H1')->getText()->createTextRun('Opcional. Debe ser menor al precio regular. Dejar vacío si no hay oferta.');

        // Comentario para campos booleanos
        $sheet->getComment('Q1')->getText()->createTextRun('SI o NO. Determina si el producto está activo en el catálogo.');
        $sheet->getComment('R1')->getText()->createTextRun('SI o NO. Determina si el producto aparece como destacado.');
    }
}
