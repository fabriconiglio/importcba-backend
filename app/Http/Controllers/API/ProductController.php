<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['category', 'brand', 'primaryImage'])
                ->active(); // Solo productos activos

            // Filtros opcionales
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->has('featured')) {
                $query->featured();
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Filtros por precio efectivo (COALESCE(sale_price, price))
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
                    $query->inStock();
                } elseif ($inStock === false) {
                    $query->where('stock_quantity', '<=', 0);
                }
            }

            // Ordenamiento
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

            // Paginación
            $perPage = (int) $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            // Transformar datos para el frontend (sin serializar el paginador)
            $products->getCollection()->transform(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'original_price' => $product->sale_price ? $product->price : null,
                    'stock_quantity' => $product->stock_quantity,
                    'is_featured' => $product->is_featured,
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
                    'image' => $product->primary_image_url,
                    'images' => $product->getImageUrls(),
                    'effective_price' => $product->getEffectivePrice(),
                    'has_discount' => $product->hasDiscount(),
                    'discount_percentage' => $product->getDiscountPercentage(),
                    'in_stock' => $product->isInStock(),
                    'low_stock' => $product->hasLowStock(),
                ];
            });

            // Construir respuesta manual para evitar traducciones de enlaces
            $payload = [
                'data' => $products->getCollection()->values(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ];

            return response()->json([
                'success' => true,
                'data' => $payload,
                'message' => 'Productos obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'category_id' => 'nullable|uuid|exists:categories,id',
                'brand_id' => 'nullable|uuid|exists:brands,id',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'min_stock_level' => 'nullable|integer|min:0',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:100',
                'is_featured' => 'nullable|boolean',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
            ]);

            $product = Product::create($validated);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Producto creado correctamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = Product::with(['category', 'brand', 'images', 'productAttributes.attribute'])
                ->findOrFail($id);

            $productData = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'original_price' => $product->sale_price ? $product->price : null,
                'stock_quantity' => $product->stock_quantity,
                'min_stock_level' => $product->min_stock_level,
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
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
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->image_url,
                        'is_primary' => $image->is_primary,
                        'alt_text' => $image->alt_text,
                        'sort_order' => $image->sort_order,
                    ];
                }),
                'attributes' => $product->productAttributes->map(function ($productAttribute) {
                    return [
                        'id' => $productAttribute->id,
                        'attribute_name' => $productAttribute->attribute->name,
                        'value' => $productAttribute->value,
                    ];
                }),
                'effective_price' => $product->getEffectivePrice(),
                'has_discount' => $product->hasDiscount(),
                'discount_percentage' => $product->getDiscountPercentage(),
                'in_stock' => $product->isInStock(),
                'low_stock' => $product->hasLowStock(),
            ];

            return response()->json([
                'success' => true,
                'data' => $productData,
                'message' => 'Producto obtenido correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'category_id' => 'nullable|uuid|exists:categories,id',
                'brand_id' => 'nullable|uuid|exists:brands,id',
                'price' => 'sometimes|required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'sometimes|required|integer|min:0',
                'min_stock_level' => 'nullable|integer|min:0',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:100',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
            ]);

            $product->update($validated);

            return response()->json([
                'success' => true,
                'data' => $product->fresh(),
                'message' => 'Producto actualizado correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category slug
     */
    public function byCategory(string $categorySlug): JsonResponse
    {
        try {
            $products = Product::with(['category', 'brand', 'primaryImage'])
                ->whereHas('category', function ($query) use ($categorySlug) {
                    $query->where('slug', $categorySlug);
                })
                ->active()
                ->latest()
                ->get();

            $products = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'image' => $product->primary_image_url,
                    'category' => $product->category->name,
                    'in_stock' => $product->isInStock(),
                    'has_discount' => $product->hasDiscount(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Productos por categoría obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos por categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured products
     */
    public function featured(): JsonResponse
    {
        try {
            $products = Product::with(['category', 'brand', 'primaryImage'])
                ->active()
                ->featured()
                ->latest()
                ->limit(20)
                ->get();

            $products = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'image' => $product->primary_image_url,
                    'category' => $product->category->name ?? null,
                    'in_stock' => $product->isInStock(),
                    'has_discount' => $product->hasDiscount(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Productos destacados obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos destacados: ' . $e->getMessage()
            ], 500);
        }
    }
}
