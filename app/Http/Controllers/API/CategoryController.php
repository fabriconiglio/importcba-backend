<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::with(['parent', 'children'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $categories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image,
                    'parent_id' => $category->parent_id,
                    'sort_order' => $category->sort_order,
                    'products_count' => $category->products()->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categorías obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías: ' . $e->getMessage()
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
                'parent_id' => 'nullable|uuid|exists:categories,id',
                'image' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
            ]);

            $category = Category::create($validated);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Categoría creada correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $category = Category::with(['parent', 'children', 'products'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Categoría obtenida correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|uuid|exists:categories,id',
                'image' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
            ]);

            $category->update($validated);

            return response()->json([
                'success' => true,
                'data' => $category->fresh(),
                'message' => 'Categoría actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories tree structure
     */
    public function tree(): JsonResponse
    {
        try {
            $categories = Category::with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->where('parent_id', null)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Árbol de categorías obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener árbol de categorías: ' . $e->getMessage()
            ], 500);
        }
    }
}
