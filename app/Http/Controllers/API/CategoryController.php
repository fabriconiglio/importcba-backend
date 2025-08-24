<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="Endpoints para gestión de categorías de productos"
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="Listar categorías activas",
     *     description="Obtiene todas las categorías activas ordenadas por sort_order",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Categorías obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categorías obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="Electrónicos"),
     *                     @OA\Property(property="slug", type="string", example="electronicos"),
     *                     @OA\Property(property="description", type="string", example="Productos electrónicos y tecnología"),
     *                     @OA\Property(property="image", type="string", example="https://example.com/category.jpg", nullable=true),
     *                     @OA\Property(property="parent_id", type="string", format="uuid", nullable=true),
     *                     @OA\Property(property="sort_order", type="integer", example=1),
     *                     @OA\Property(property="products_count", type="integer", example=25)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/categories",
     *     summary="Crear categoría",
     *     description="Crea una nueva categoría de productos",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Electrónicos", description="Nombre de la categoría"),
     *             @OA\Property(property="description", type="string", example="Productos electrónicos y tecnología", description="Descripción de la categoría"),
     *             @OA\Property(property="parent_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID de la categoría padre (opcional)"),
     *             @OA\Property(property="image", type="string", example="https://example.com/category.jpg", description="URL de la imagen de la categoría"),
     *             @OA\Property(property="sort_order", type="integer", example=1, description="Orden de clasificación"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Estado activo de la categoría")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Categoría creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categoría creada correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/categories/{id}",
     *     summary="Obtener categoría específica por ID",
     *     description="Obtiene los detalles de una categoría específica por su ID incluyendo sus subcategorías y productos",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la categoría",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categoría obtenida correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/categories/slug/{slug}",
     *     summary="Obtener categoría específica por slug",
     *     description="Obtiene los detalles de una categoría específica por su slug incluyendo sus subcategorías y productos",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug de la categoría",
     *         required=true,
     *         @OA\Schema(type="string", example="bazar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categoría obtenida correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function bySlug(string $slug): JsonResponse
    {
        try {
            $category = Category::with(['parent', 'children', 'products'])
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

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
     * @OA\Put(
     *     path="/api/v1/categories/{id}",
     *     summary="Actualizar categoría",
     *     description="Actualiza los datos de una categoría existente",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la categoría",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Electrónicos Avanzados", description="Nombre de la categoría"),
     *             @OA\Property(property="description", type="string", example="Productos electrónicos de última generación", description="Descripción de la categoría"),
     *             @OA\Property(property="parent_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID de la categoría padre (opcional)"),
     *             @OA\Property(property="image", type="string", example="https://example.com/new-category.jpg", description="URL de la imagen de la categoría"),
     *             @OA\Property(property="sort_order", type="integer", example=2, description="Orden de clasificación"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Estado activo de la categoría")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categoría actualizada correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     summary="Eliminar categoría",
     *     description="Elimina una categoría existente",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la categoría",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categoría eliminada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/categories/tree/list",
     *     summary="Obtener árbol de categorías",
     *     description="Obtiene la estructura jerárquica de categorías activas",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Árbol de categorías obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Árbol de categorías obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="Electrónicos"),
     *                     @OA\Property(property="slug", type="string", example="electronicos"),
     *                     @OA\Property(property="description", type="string", example="Productos electrónicos y tecnología"),
     *                     @OA\Property(property="image", type="string", example="https://example.com/category.jpg", nullable=true),
     *                     @OA\Property(property="parent_id", type="string", format="uuid", nullable=true),
     *                     @OA\Property(property="sort_order", type="integer", example=1),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="children",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Category")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function tree(): JsonResponse
    {
        try {
            $categories = Category::with([
                'children' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                },
                'brands' => function ($query) {
                    $query->where('is_active', true)->orderByPivot('sort_order');
                }
            ])
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
