<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// MOD-026 (main): Creado sistema de importación para crear nuevos productos desde Excel
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
     * Procesar una fila individual para crear un nuevo producto
     */
    protected function processRow($row, $rowNumber)
    {
        // Validar campos obligatorios
        if (empty($row['nombre'])) {
            throw new \Exception("El nombre del producto es obligatorio");
        }

        if (empty($row['precio'])) {
            throw new \Exception("El precio del producto es obligatorio");
        }

        // Verificar que no exista un producto con el mismo SKU (si se proporciona)
        if (!empty($row['sku'])) {
            $existingProduct = Product::where('sku', $row['sku'])->first();
            if ($existingProduct) {
                throw new \Exception("Ya existe un producto con el SKU: {$row['sku']}");
            }
        }

        // Preparar datos para creación
        $productData = [];

        // Campos obligatorios
        $productData['name'] = trim($row['nombre']);
        $productData['price'] = floatval($row['precio']);

        // Generar SKU automático si no se proporciona
        if (!empty($row['sku'])) {
            $productData['sku'] = trim($row['sku']);
        } else {
            $productData['sku'] = $this->generateUniqueSku($productData['name']);
        }

        // Generar slug único
        $productData['slug'] = $this->generateUniqueSlug($productData['name']);

        // Campos opcionales con valores por defecto
        $productData['description'] = !empty($row['descripcion']) ? trim($row['descripcion']) : '';
        $productData['short_description'] = !empty($row['descripcion_corta']) ? trim($row['descripcion_corta']) : '';
        
        // Precios
        if (!empty($row['precio_oferta']) && floatval($row['precio_oferta']) > 0) {
            $productData['sale_price'] = floatval($row['precio_oferta']);
        }

        // Stock
        $productData['stock_quantity'] = !empty($row['stock']) ? intval($row['stock']) : 0;
        $productData['min_stock_level'] = !empty($row['stock_minimo']) ? intval($row['stock_minimo']) : 0;

        // SEO
        $productData['meta_title'] = !empty($row['meta_titulo']) ? trim($row['meta_titulo']) : $productData['name'];
        $productData['meta_description'] = !empty($row['meta_descripcion']) ? trim($row['meta_descripcion']) : Str::limit($productData['description'], 150);

        // Manejar categoría
        if (!empty($row['categoria'])) {
            $category = Category::where('name', 'ILIKE', '%' . trim($row['categoria']) . '%')->first();
            if ($category) {
                $productData['category_id'] = $category->id;
            } else {
                throw new \Exception("Categoría '{$row['categoria']}' no encontrada. Debe existir previamente en el sistema.");
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
        if (!empty($row['marca'])) {
            $brand = Brand::where('name', 'ILIKE', '%' . trim($row['marca']) . '%')->first();
            if ($brand) {
                $productData['brand_id'] = $brand->id;
            } else {
                throw new \Exception("Marca '{$row['marca']}' no encontrada. Debe existir previamente en el sistema.");
            }
        }

        // Campos booleanos
        $productData['is_active'] = true; // Por defecto activo
        if (isset($row['activo'])) {
            $productData['is_active'] = strtoupper(trim($row['activo'])) === 'SI';
        }

        $productData['is_featured'] = false; // Por defecto no destacado
        if (isset($row['destacado'])) {
            $productData['is_featured'] = strtoupper(trim($row['destacado'])) === 'SI';
        }

        // Peso y dimensiones (opcionales)
        if (!empty($row['peso'])) {
            $productData['weight'] = floatval($row['peso']);
        }

        if (!empty($row['largo'])) {
            $productData['length'] = floatval($row['largo']);
        }

        if (!empty($row['ancho'])) {
            $productData['width'] = floatval($row['ancho']);
        }

        if (!empty($row['alto'])) {
            $productData['height'] = floatval($row['alto']);
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
