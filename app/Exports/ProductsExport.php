<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// MOD-001 (main): Creado sistema de exportación de productos a Excel para actualización masiva
/** @phpstan-ignore-next-line */
class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles
{
    protected $categoryId;
    protected $brandId;

    public function __construct($categoryId = null, $brandId = null)
    {
        $this->categoryId = $categoryId;
        $this->brandId = $brandId;
    }

    /**
     * Obtener la colección de productos a exportar
     */
    public function collection()
    {
        $query = Product::with(['category', 'brand', 'primaryImage']);

        // Filtrar por categoría si se especifica
        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        // Filtrar por marca si se especifica
        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Definir los encabezados del Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'SKU',
            'Descripción',
            'Categoría',
            'Marca',
            'Precio',
            'Precio Oferta',
            'Stock',
            'Stock Mínimo',
            'Meta Título',
            'Imagen Principal',
            'Activo',
            'Destacado'
        ];
    }

    /**
     * Mapear los datos de cada producto
     */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->description,
            $product->category?->name ?? '',
            $product->brand?->name ?? '',
            $product->price,
            $product->sale_price,
            $product->stock_quantity,
            $product->min_stock_level,
            $product->meta_title,
            $product->primary_image_url,
            $product->is_active ? 'SI' : 'NO',
            $product->is_featured ? 'SI' : 'NO'
        ];
    }

    /**
     * Formatear columnas específicas
     */
    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_00, // Precio
            'H' => NumberFormat::FORMAT_NUMBER_00, // Precio Oferta
            'I' => NumberFormat::FORMAT_NUMBER,    // Stock
            'J' => NumberFormat::FORMAT_NUMBER,    // Stock Mínimo
        ];
    }

    /**
     * Aplicar estilos al Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para los encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
        ];
    }
}
