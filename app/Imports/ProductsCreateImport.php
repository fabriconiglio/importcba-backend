<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

// MOD-027 (main): Reescrito sistema de importación para crear productos - sin WithHeadingRow para evitar problemas
/** 
 * @phpstan-ignore-next-line 
 * @psalm-suppress UndefinedClass
 */
class ProductsCreateImport implements ToCollection
{
    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;
    protected $createdProducts = [];

    /**
     * Procesar la colección de datos del Excel
     * Sin WithHeadingRow - manejo manual de headers
     */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errors[] = "El archivo Excel está vacío";
            return;
        }

        // Primera fila son los headers
        $headers = $rows->first()->toArray();
        $this->errors[] = "DEBUG - Headers encontrados: " . implode(', ', $headers);
        
        // Mapear posiciones de columnas
        $columnMap = $this->mapColumns($headers);
        
        // Procesar filas de datos (omitir la primera que son headers)
        foreach ($rows->skip(1) as $index => $row) {
            try {
                $this->processRowManual($row->toArray(), $columnMap, $index + 2); // +2 porque la fila 1 son headers
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    /**
     * Mapear headers a índices de columnas
     */
    protected function mapColumns(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            $map[$normalizedHeader] = $index;
        }
        return $map;
    }

    /**
     * Procesar una fila individual para crear un nuevo producto
     * Método manual sin depender de WithHeadingRow
     */
    protected function processRowManual(array $rowData, array $columnMap, int $rowNumber)
    {
        // Helper para obtener valor de columna de forma segura
        $getValue = function($columnName) use ($rowData, $columnMap) {
            $normalizedName = strtolower(trim($columnName));
            if (isset($columnMap[$normalizedName])) {
                $index = $columnMap[$normalizedName];
                return isset($rowData[$index]) ? $rowData[$index] : null;
            }
            return null;
        };

        // Debug de la fila actual
        $this->errors[] = "DEBUG Fila {$rowNumber} - Procesando " . count($rowData) . " columnas";
        
        // Validar campos obligatorios
        $nombre = $getValue('nombre');
        $precio = $getValue('precio');
        
        if (empty($nombre)) {
            throw new \Exception("El nombre del producto es obligatorio");
        }

        if (empty($precio)) {
            throw new \Exception("El precio del producto es obligatorio");
        }

        // Obtener valores usando la función helper
        $sku = $getValue('sku');
        $descripcion = $getValue('descripcion');
        $descripcion_corta = $getValue('descripcion_corta');
        $categoria = $getValue('categoria');
        $marca = $getValue('marca');
        $precio_oferta = $getValue('precio_oferta');
        $stock = $getValue('stock');
        $stock_minimo = $getValue('stock_minimo');
        $meta_titulo = $getValue('meta_titulo');
        $meta_descripcion = $getValue('meta_descripcion');
        $peso_kg = $getValue('peso_kg');
        $largo_cm = $getValue('largo_cm');
        $ancho_cm = $getValue('ancho_cm');
        $alto_cm = $getValue('alto_cm');
        $activo = $getValue('activo');
        $destacado = $getValue('destacado');

        // Verificar que no exista un producto con el mismo SKU (si se proporciona)
        if (!empty($sku)) {
            $existingProduct = Product::where('sku', $sku)->first();
            if ($existingProduct) {
                throw new \Exception("Ya existe un producto con el SKU: {$sku}");
            }
        }

        // Preparar datos para creación
        $productData = [];

        // Campos obligatorios
        $productData['name'] = trim($nombre);
        $productData['price'] = floatval($precio);

        // Generar SKU automático si no se proporciona
        if (!empty($sku)) {
            $productData['sku'] = trim($sku);
        } else {
            $productData['sku'] = $this->generateUniqueSku($productData['name']);
        }

        // Generar slug único
        $productData['slug'] = $this->generateUniqueSlug($productData['name']);

        // Campos opcionales con valores por defecto
        $productData['description'] = !empty($descripcion) ? trim($descripcion) : '';
        $productData['short_description'] = !empty($descripcion_corta) ? trim($descripcion_corta) : '';
        
        // Precios
        if (!empty($precio_oferta) && floatval($precio_oferta) > 0) {
            $productData['sale_price'] = floatval($precio_oferta);
        }

        // Stock
        $productData['stock_quantity'] = !empty($stock) ? intval($stock) : 0;
        $productData['min_stock_level'] = !empty($stock_minimo) ? intval($stock_minimo) : 0;

        // SEO
        $productData['meta_title'] = !empty($meta_titulo) ? trim($meta_titulo) : $productData['name'];
        $productData['meta_description'] = !empty($meta_descripcion) ? trim($meta_descripcion) : Str::limit($productData['description'], 150);

        // Manejar categoría
        if (!empty($categoria)) {
            $category = Category::where('name', 'ILIKE', '%' . trim($categoria) . '%')->first();
            if ($category) {
                $productData['category_id'] = $category->id;
            } else {
                throw new \Exception("Categoría '{$categoria}' no encontrada. Debe existir previamente en el sistema.");
            }
        } else {
            // Buscar categoría por defecto o crear una genérica
            $defaultCategory = Category::where('name', 'ILIKE', '%general%')->orWhere('name', 'ILIKE', '%sin categoria%')->first();
            if ($defaultCategory) {
                $productData['category_id'] = $defaultCategory->id;
            } else {
                throw new \Exception("No se especificó categoría y no existe una categoría por defecto. Agrega la columna 'categoria' o crea una categoría 'General'.");
            }
        }

        // Manejar marca
        if (!empty($marca)) {
            $brand = Brand::where('name', 'ILIKE', '%' . trim($marca) . '%')->first();
            if ($brand) {
                $productData['brand_id'] = $brand->id;
            } else {
                throw new \Exception("Marca '{$marca}' no encontrada. Debe existir previamente en el sistema.");
            }
        }

        // Campos booleanos
        $productData['is_active'] = true; // Por defecto activo
        if (!empty($activo)) {
            $productData['is_active'] = strtoupper(trim($activo)) === 'SI';
        }

        $productData['is_featured'] = false; // Por defecto no destacado
        if (!empty($destacado)) {
            $productData['is_featured'] = strtoupper(trim($destacado)) === 'SI';
        }

        // Peso y dimensiones (opcionales)
        if (!empty($peso_kg)) {
            $productData['weight'] = floatval($peso_kg);
        }

        if (!empty($largo_cm)) {
            $productData['length'] = floatval($largo_cm);
        }

        if (!empty($ancho_cm)) {
            $productData['width'] = floatval($ancho_cm);
        }

        if (!empty($alto_cm)) {
            $productData['height'] = floatval($alto_cm);
        }

        // Validaciones de negocio
        $this->validateProductData($productData);

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
     * Validar datos del producto antes de crear
     */
    protected function validateProductData(array $data)
    {
        // Validar precio
        if ($data['price'] <= 0) {
            throw new \Exception("El precio debe ser mayor a 0");
        }

        // Validar precio de oferta
        if (isset($data['sale_price']) && $data['sale_price'] !== null) {
            if ($data['sale_price'] <= 0) {
                throw new \Exception("El precio de oferta debe ser mayor a 0 o estar vacío");
            }
            if ($data['sale_price'] >= $data['price']) {
                throw new \Exception("El precio de oferta debe ser menor al precio regular");
            }
        }

        // Validar stock
        if ($data['stock_quantity'] < 0) {
            throw new \Exception("El stock no puede ser negativo");
        }

        if ($data['min_stock_level'] < 0) {
            throw new \Exception("El stock mínimo no puede ser negativo");
        }

        // Validar peso y dimensiones
        $numericFields = ['weight', 'length', 'width', 'height'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && $data[$field] < 0) {
                throw new \Exception("El campo {$field} no puede ser negativo");
            }
        }
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
