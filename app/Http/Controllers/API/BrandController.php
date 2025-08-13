<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Http\Resources\BrandResource;
use App\Http\Resources\BrandCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Brand::query();

            // Filtros
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $brands = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => new BrandCollection($brands),
                'message' => 'Marcas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas: ' . $e->getMessage()
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
                'name' => 'required|string|max:255|unique:brands,name',
                'description' => 'nullable|string|max:1000',
                'logo_url' => 'nullable|url|max:500',
                'is_active' => 'boolean',
            ]);

            $brand = Brand::create($validated);

            return response()->json([
                'success' => true,
                'data' => new BrandResource($brand),
                'message' => 'Marca creada correctamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear marca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $brand = Brand::with('products')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new BrandResource($brand),
                'message' => 'Marca obtenida correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $brand = Brand::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:brands,name,' . $id,
                'description' => 'nullable|string|max:1000',
                'logo_url' => 'nullable|url|max:500',
                'is_active' => 'boolean',
            ]);

            $brand->update($validated);

            return response()->json([
                'success' => true,
                'data' => new BrandResource($brand),
                'message' => 'Marca actualizada correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar marca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $brand = Brand::findOrFail($id);

            // Verificar si tiene productos asociados
            if ($brand->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la marca porque tiene productos asociados'
                ], 422);
            }

            $brand->delete();

            return response()->json([
                'success' => true,
                'message' => 'Marca eliminada correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar marca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener marcas activas para el frontend
     */
    public function active(): JsonResponse
    {
        try {
            $brands = Brand::active()
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => BrandResource::collection($brands),
                'message' => 'Marcas activas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas activas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener marca por slug
     */
    public function bySlug(string $slug): JsonResponse
    {
        try {
            $brand = Brand::with('products')
                ->bySlug($slug)
                ->active()
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new BrandResource($brand),
                'message' => 'Marca obtenida correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marca: ' . $e->getMessage()
            ], 500);
        }
    }
}
