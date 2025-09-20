<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// MOD-028 (main): Reescrito sistema de importación para crear productos - usando WithHeadingRow como el de actualización
/** 
 * @phpstan-ignore-next-line 
 * @psalm-suppress UndefinedClass
 */
class ProductsCreateImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;
    protected $createdProducts = [];

    /**
     * Procesar la colección de datos del Excel
     * MOD-028 (main): Usando WithHeadingRow como el import de actualización que funciona
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row, $index + 2); // +2 porque empezamos desde la fila 2 (después del header)
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }


    /**
     * Procesar una fila individual para crear un nuevo producto
     * MOD-028 (main): Adaptado del método que funciona en ProductsImport
     */
    protected function processRow($row, $rowNumber)
    {
        // Validar campos obligatorios (igual que el import que funciona)
        if (empty($row['nombre'])) {
            throw new \Exception("El nombre del producto es obligatorio");
        }

        if (empty($row['precio']) || $row['precio'] <= 0) {
            throw new \Exception("El precio del producto es obligatorio y debe ser mayor a 0");
        }

        // Verificar que no exista un producto con el mismo SKU (si se proporciona)
        if (!empty($row['sku'])) {
            $existingProduct = Product::where('sku', $row['sku'])->first();
            if ($existingProduct) {
                throw new \Exception("Ya existe un producto con el SKU: {$row['sku']}");
            }
        }

        // Preparar datos para creación (usando misma estructura que el que funciona)
        $productData = [];

        // Campos directos (siguiendo patrón del import de actualización)
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
                $productData[$dbField] = $row[$excelField];
            }
        }

        // Generar SKU automático si no se proporciona
        if (!empty($row['sku'])) {
            $productData['sku'] = trim($row['sku']);
        } else {
            $productData['sku'] = $this->generateUniqueSku($productData['name']);
        }

        // Generar slug único
        $productData['slug'] = $this->generateUniqueSlug($productData['name']);

        // Campos opcionales
        $productData['short_description'] = $row['descripcion_corta'] ?? '';
        $productData['meta_description'] = $row['meta_descripcion'] ?? Str::limit($productData['description'] ?? '', 150);

        // Dimensiones y peso
        if (!empty($row['peso_kg'])) {
            $productData['weight'] = floatval($row['peso_kg']);
        }
        if (!empty($row['largo_cm'])) {
            $productData['length'] = floatval($row['largo_cm']);
        }
        if (!empty($row['ancho_cm'])) {
            $productData['width'] = floatval($row['ancho_cm']);
        }
        if (!empty($row['alto_cm'])) {
            $productData['height'] = floatval($row['alto_cm']);
        }

        // Manejar categoría (igual que el import que funciona)
        if (!empty($row['categoria'])) {
            $category = Category::where('name', 'ILIKE', '%' . trim($row['categoria']) . '%')->first();
            if ($category) {
                $productData['category_id'] = $category->id;
            } else {
                throw new \Exception("Categoría '{$row['categoria']}' no encontrada");
            }
        }

        // Manejar marca (igual que el import que funciona)
        if (!empty($row['marca'])) {
            $brand = Brand::where('name', 'ILIKE', '%' . trim($row['marca']) . '%')->first();
            if ($brand) {
                $productData['brand_id'] = $brand->id;
            } else {
                throw new \Exception("Marca '{$row['marca']}' no encontrada");
            }
        }

        // Manejar campos booleanos (igual que el import que funciona)
        $productData['is_active'] = true; // Por defecto activo
        if (isset($row['activo'])) {
            $productData['is_active'] = strtoupper(trim($row['activo'])) === 'SI';
        }

        $productData['is_featured'] = false; // Por defecto no destacado
        if (isset($row['destacado'])) {
            $productData['is_featured'] = strtoupper(trim($row['destacado'])) === 'SI';
        }

        // Validar precios (igual que el import que funciona)
        if (isset($productData['price']) && $productData['price'] <= 0) {
            throw new \Exception("El precio debe ser mayor a 0");
        }

        if (isset($productData['sale_price']) && $productData['sale_price'] !== null && $productData['sale_price'] <= 0) {
            throw new \Exception("El precio de oferta debe ser mayor a 0 o estar vacío");
        }

        // Validar stock (igual que el import que funciona)
        if (isset($productData['stock_quantity']) && $productData['stock_quantity'] < 0) {
            throw new \Exception("El stock no puede ser negativo");
        }

        if (isset($productData['min_stock_level']) && $productData['min_stock_level'] < 0) {
            throw new \Exception("El stock mínimo no puede ser negativo");
        }

        // Crear el producto
        $product = Product::create($productData);
        
        $this->successCount++;
        $this->createdProducts[] = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price
        ];
    }


    /**
     * Generar SKU único basado en el nombre
     */
    protected function generateUniqueSku(string $name): string
    {
        $baseSku = Str::upper(Str::slug($name, ''));
        $baseSku = substr($baseSku, 0, 10); // Limitar a 10 caracteres
        
        $sku = $baseSku;
        $counter = 1;
        
        while (Product::where('sku', $sku)->exists()) {
            $sku = $baseSku . $counter;
            $counter++;
        }
        
        return $sku;
    }

    /**
     * Generar slug único basado en el nombre
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
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
            'total_processed' => $this->successCount + $this->errorCount,
            'created_products' => $this->createdProducts
        ];
    }

    /**
     * Obtener productos creados
     */
    public function getCreatedProducts(): array
    {
        return $this->createdProducts;
    }
}
