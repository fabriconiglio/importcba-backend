<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// MOD-027 (main): Creada plantilla Excel para asignación masiva de marcas a productos
class ProductBrandAssignmentExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $categoryId;
    protected $currentBrandId;

    public function __construct($categoryId = null, $currentBrandId = null)
    {
        $this->categoryId = $categoryId;
        $this->currentBrandId = $currentBrandId;
    }

    /**
     * Obtener productos para asignación de marcas
     */
    public function collection()
    {
        $query = Product::query()
            ->select('id', 'sku', 'name', 'brand_id')
            ->with('brand:id,name');

        // Filtrar por categoría si se especifica
        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        // Filtrar por marca actual si se especifica (ej: productos sin marca)
        if ($this->currentBrandId === 'null') {
            $query->whereNull('brand_id');
        } elseif ($this->currentBrandId) {
            $query->where('brand_id', $this->currentBrandId);
        }

        return $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'nombre' => $product->name,
                'marca_actual' => $product->brand ? $product->brand->name : '',
                'nueva_marca' => '', // Campo vacío para completar
            ];
        });
    }

    /**
     * Encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'ID (*)',
            'SKU',
            'NOMBRE PRODUCTO',
            'MARCA ACTUAL',
            'NUEVA MARCA (*)'
        ];
    }

    /**
     * Estilos de la hoja
     */
    public function styles(Worksheet $sheet)
    {
        // Obtener el número total de filas con datos
        $totalRows = $this->collection()->count() + 1; // +1 por los headers

        // Estilo para los encabezados
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7c3aed'] // Púrpura para marcas
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
        if ($totalRows > 1) {
            $sheet->getStyle("A2:E{$totalRows}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ]);
        }

        // Resaltar campos obligatorios (ID y NUEVA MARCA)
        $sheet->getStyle('A1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'dc2626'] // Rojo para obligatorios
            ]
        ]);

        $sheet->getStyle('E1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'dc2626'] // Rojo para obligatorios
            ]
        ]);

        // Bloquear columnas que no se deben editar (ID, SKU, NOMBRE, MARCA ACTUAL)
        $sheet->getStyle("A2:D{$totalRows}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f3f4f6'] // Gris claro para solo lectura
            ]
        ]);

        // Resaltar la columna de NUEVA MARCA (la única editable)
        $sheet->getStyle("E2:E{$totalRows}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'fef3c7'] // Amarillo claro para editable
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
            'A' => 8,  // ID
            'B' => 15, // SKU
            'C' => 40, // NOMBRE PRODUCTO
            'D' => 20, // MARCA ACTUAL
            'E' => 25, // NUEVA MARCA
        ];
    }

    /**
     * Agregar comentarios explicativos a las celdas
     */
    protected function addComments(Worksheet $sheet)
    {
        // Comentario para ID
        $sheet->getComment('A1')->getText()->createTextRun(
            'ID único del producto. NO MODIFICAR. Se usa para identificar qué producto actualizar.'
        );

        // Comentario para NUEVA MARCA
        $sheet->getComment('E1')->getText()->createTextRun(
            'Escribe el nombre exacto de la marca a asignar. La marca debe existir en el sistema. Ejemplos: Samsung, LG, Sony, etc.'
        );

        // Agregar lista de marcas disponibles en una celda especial
        $availableBrands = Brand::orderBy('name')->pluck('name')->take(20)->implode(', ');
        
        $sheet->setCellValue('G1', 'MARCAS DISPONIBLES:');
        $sheet->setCellValue('G2', $availableBrands);
        
        $sheet->getStyle('G1:G2')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'e0f2fe']
            ],
            'alignment' => ['wrapText' => true]
        ]);

        // Ajustar ancho de la columna de marcas disponibles
        $sheet->getColumnDimension('G')->setWidth(50);
    }
}
