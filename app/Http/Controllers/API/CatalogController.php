<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CatalogController extends Controller
{
    /**
     * Get products by category slug (SEO-friendly)
     * URL: /api/v1/catalog/category/{categorySlug}
     */
    public function byCategory(string $categorySlug, Request $request): JsonResponse
    {
        try {
            // Buscar la categoría por slug
            $category = Category::where('slug', $categorySlug)
                ->where('is_active', true)
                ->firstOrFail();

            $query = Product::with(['category', 'brand', 'primaryImage', 'images'])
                ->where('category_id', $category->id)
                ->active();

            // Aplicar filtros adicionales
            $this->applyFilters($query, $request);

            // Aplicar ordenamiento
            $this->applySorting($query, $request);

            // Paginación
            $perPage = (int) $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            // Transformar productos
            $products->getCollection()->transform(function ($product) {
                return $this->transformProduct($product);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->items(),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ],
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'image' => $category->image,
                    ],
                    'filters' => [
                        'applied' => $this->getAppliedFilters($request),
                        'available' => $this->getAvailableFilters($category->id),
                    ]
                ],
                'message' => "Productos de la categoría '{$category->name}' obtenidos correctamente"
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos por categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by brand slug (SEO-friendly)
     * URL: /api/v1/catalog/brand/{brandSlug}
     */
    public function byBrand(string $brandSlug, Request $request): JsonResponse
    {
        try {
            // Buscar la marca por slug
            $brand = Brand::where('slug', $brandSlug)
                ->where('is_active', true)
                ->firstOrFail();

            $query = Product::with(['category', 'brand', 'primaryImage', 'images'])
                ->where('brand_id', $brand->id)
                ->active();

            // Aplicar filtros adicionales
            $this->applyFilters($query, $request);

            // Aplicar ordenamiento
            $this->applySorting($query, $request);

            // Paginación
            $perPage = (int) $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            // Transformar productos
            $products->getCollection()->transform(function ($product) {
                return $this->transformProduct($product);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->items(),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ],
                    'brand' => [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'description' => $brand->description,
                        'logo_url' => $brand->logo_url,
                    ],
                    'filters' => [
                        'applied' => $this->getAppliedFilters($request),
                        'available' => $this->getAvailableFilters(null, $brand->id),
                    ]
                ],
                'message' => "Productos de la marca '{$brand->name}' obtenidos correctamente"
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos por marca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category and brand (SEO-friendly)
     * URL: /api/v1/catalog/category/{categorySlug}/brand/{brandSlug}
     */
    public function byCategoryAndBrand(string $categorySlug, string $brandSlug, Request $request): JsonResponse
    {
        try {
            // Buscar la categoría por slug
            $category = Category::where('slug', $categorySlug)
                ->where('is_active', true)
                ->firstOrFail();

            // Buscar la marca por slug
            $brand = Brand::where('slug', $brandSlug)
                ->where('is_active', true)
                ->firstOrFail();

            $query = Product::with(['category', 'brand', 'primaryImage', 'images'])
                ->where('category_id', $category->id)
                ->where('brand_id', $brand->id)
                ->active();

            // Aplicar filtros adicionales
            $this->applyFilters($query, $request);

            // Aplicar ordenamiento
            $this->applySorting($query, $request);

            // Paginación
            $perPage = (int) $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            // Transformar productos
            $products->getCollection()->transform(function ($product) {
                return $this->transformProduct($product);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->items(),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ],
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'image' => $category->image,
                    ],
                    'brand' => [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'description' => $brand->description,
                        'logo_url' => $brand->logo_url,
                    ],
                    'filters' => [
                        'applied' => $this->getAppliedFilters($request),
                        'available' => $this->getAvailableFilters($category->id, $brand->id),
                    ]
                ],
                'message' => "Productos de '{$category->name}' - '{$brand->name}' obtenidos correctamente"
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría o marca no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filtros por precio efectivo
        if ($request->filled('price_min')) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [(float) $request->price_min]);
        }

        if ($request->filled('price_max')) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $request->price_max]);
        }

        // Filtro por stock
        if ($request->has('in_stock')) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($inStock === true) {
                $query->where('stock_quantity', '>', 0);
            } elseif ($inStock === false) {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        // Filtro por productos destacados
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Filtro por descuento
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price')
                  ->where('sale_price', '>', 0);
        }
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting($query, Request $request): void
    {
        $sort = (string) $request->get('sort', 'newest');
        
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    /**
     * Transform product data for response
     */
    private function transformProduct($product): array
    {
        $effectivePrice = $product->sale_price ?: $product->price;
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'effective_price' => $effectivePrice,
            'original_price' => $product->sale_price ? $product->price : null,
            'stock_quantity' => $product->stock_quantity,
            'in_stock' => $product->stock_quantity > 0,
            'low_stock' => $product->stock_quantity > 0 && $product->stock_quantity <= 10,
            'is_featured' => $product->is_featured,
            'has_discount' => $product->sale_price && $product->sale_price > 0,
            'discount_percentage' => $product->sale_price ? round((($product->price - $product->sale_price) / $product->price) * 100) : 0,
            'primary_image' => $product->primary_image_url,
            'images' => $product->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                    'is_primary' => $image->is_primary,
                    'order' => $image->order,
                ];
            }),
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }

    /**
     * Get applied filters from request
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        if ($request->filled('search')) {
            $filters['search'] = $request->get('search');
        }
        
        if ($request->filled('price_min')) {
            $filters['price_min'] = $request->get('price_min');
        }
        
        if ($request->filled('price_max')) {
            $filters['price_max'] = $request->get('price_max');
        }
        
        if ($request->has('in_stock')) {
            $filters['in_stock'] = $request->boolean('in_stock');
        }
        
        if ($request->boolean('featured')) {
            $filters['featured'] = true;
        }
        
        if ($request->boolean('on_sale')) {
            $filters['on_sale'] = true;
        }
        
        if ($request->filled('sort')) {
            $filters['sort'] = $request->get('sort');
        }
        
        return $filters;
    }

    /**
     * Get available filters for the current context
     */
    private function getAvailableFilters(?string $categoryId = null, ?string $brandId = null): array
    {
        $query = Product::active();
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }
        
        $products = $query->get();
        
        $priceRange = [
            'min' => $products->min(function ($product) {
                return $product->sale_price ?: $product->price;
            }),
            'max' => $products->max(function ($product) {
                return $product->sale_price ?: $product->price;
            }),
        ];
        
        return [
            'price_range' => $priceRange,
            'total_products' => $products->count(),
            'in_stock_count' => $products->where('stock_quantity', '>', 0)->count(),
            'featured_count' => $products->where('is_featured', true)->count(),
            'on_sale_count' => $products->whereNotNull('sale_price')->where('sale_price', '>', 0)->count(),
        ];
    }
}
