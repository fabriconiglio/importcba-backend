<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// MOD-002 (main): Creado sistema de importación de productos desde Excel para actualización masiva
/** 
 * @phpstan-ignore-next-line 
 * @psalm-suppress UndefinedClass
 */
class ProductsImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;

    /**
     * Procesar la colección de datos del Excel
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Debug: mostrar headers en la primera fila
                if ($index === 0) {
                    $headers = array_keys($row->toArray());
                    $this->errors[] = "DEBUG - Headers encontrados: " . implode(', ', $headers);
                }
                
                $this->processRow($row, $index + 2); // +2 porque empezamos desde la fila 2 (después del header)
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    /**
     * Procesar una fila individual
     */
    protected function processRow($row, $rowNumber)
    {
        // Buscar el producto por ID o SKU (headers del Excel convertidos por Laravel Excel)
        $product = null;
        
        if (!empty($row['id'])) {
            $product = Product::find($row['id']);
        } elseif (!empty($row['sku'])) {
            $product = Product::where('sku', $row['sku'])->first();
        }

        if (!$product) {
            throw new \Exception("Producto no encontrado con ID: {$row['id']} o SKU: {$row['sku']}");
        }

        // Preparar datos para actualización
        $updateData = [];

        // Campos que se pueden actualizar directamente (headers del Excel tal como aparecen)
        $directFields = [
            'nombre' => 'name',
            'descripcion' => 'description', 
            'precio' => 'price',
            'precio_oferta' => 'sale_price',
            'stock' => 'stock_quantity',
            'stock_minimo' => 'min_stock_level',
            'meta_titulo' => 'meta_title'
        ];

        foreach ($directFields as $excelField => $dbField) {
            if (isset($row[$excelField]) && $row[$excelField] !== null && $row[$excelField] !== '') {
                $updateData[$dbField] = $row[$excelField];
            }
        }

        // Manejar categoría
        if (!empty($row['categoria'])) {
            $category = Category::where('name', 'ILIKE', '%' . trim($row['categoria']) . '%')->first();
            if ($category) {
                $updateData['category_id'] = $category->id;
            } else {
                $this->errors[] = "Fila $rowNumber: Categoría '{$row['categoria']}' no encontrada";
            }
        }

        // Manejar marca
        if (!empty($row['marca'])) {
            $brand = Brand::where('name', 'ILIKE', '%' . trim($row['marca']) . '%')->first();
            if ($brand) {
                $updateData['brand_id'] = $brand->id;
            } else {
                $this->errors[] = "Fila $rowNumber: Marca '{$row['marca']}' no encontrada";
            }
        }

        // Manejar campos booleanos
        if (isset($row['activo'])) {
            $updateData['is_active'] = strtoupper(trim($row['activo'])) === 'SI';
        }

        if (isset($row['destacado'])) {
            $updateData['is_featured'] = strtoupper(trim($row['destacado'])) === 'SI';
        }

        // Validar precios
        if (isset($updateData['price']) && $updateData['price'] <= 0) {
            throw new \Exception("El precio debe ser mayor a 0");
        }

        if (isset($updateData['sale_price']) && $updateData['sale_price'] !== null && $updateData['sale_price'] <= 0) {
            throw new \Exception("El precio de oferta debe ser mayor a 0 o estar vacío");
        }

        // Validar stock
        if (isset($updateData['stock_quantity']) && $updateData['stock_quantity'] < 0) {
            throw new \Exception("El stock no puede ser negativo");
        }

        if (isset($updateData['min_stock_level']) && $updateData['min_stock_level'] < 0) {
            throw new \Exception("El stock mínimo no puede ser negativo");
        }

        // Actualizar el producto si hay datos para actualizar
        if (!empty($updateData)) {
            $product->update($updateData);
            $this->successCount++;
        }
    }


    /**
     * Obtener errores de procesamiento
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener estadísticas de procesamiento
     */
    public function getStats(): array
    {
        return [
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'total_processed' => $this->successCount + $this->errorCount
        ];
    }
}
