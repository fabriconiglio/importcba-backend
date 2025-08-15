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

/**
 * @OA\Tag(
 *     name="Brands",
 *     description="Endpoints para gestión de marcas de productos"
 * )
 */
class BrandController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/brands",
     *     summary="Listar marcas",
     *     description="Obtiene una lista paginada de marcas con filtros opcionales",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filtrar por estado activo",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nombre o descripción",
     *         required=false,
     *         @OA\Schema(type="string", example="Apple")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", default="name", enum={"name", "created_at", "updated_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Orden de clasificación",
     *         required=false,
     *         @OA\Schema(type="string", default="asc", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Número de elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marcas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marcas obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Brand")),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
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
     * @OA\Post(
     *     path="/api/v1/brands",
     *     summary="Crear marca",
     *     description="Crea una nueva marca de producto",
     *     tags={"Brands"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Apple", description="Nombre de la marca"),
     *             @OA\Property(property="description", type="string", maxLength=1000, example="Empresa líder en tecnología", description="Descripción de la marca"),
     *             @OA\Property(property="logo_url", type="string", format="url", maxLength=500, example="https://example.com/logo.png", description="URL del logo de la marca"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Estado activo de la marca")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Marca creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marca creada correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Brand")
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
     * @OA\Get(
     *     path="/api/v1/brands/{id}",
     *     summary="Obtener marca específica",
     *     description="Obtiene los detalles de una marca específica incluyendo sus productos",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la marca",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marca obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marca obtenida correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Brand")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marca no encontrada",
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
     * @OA\Put(
     *     path="/api/v1/brands/{id}",
     *     summary="Actualizar marca",
     *     description="Actualiza los datos de una marca existente",
     *     tags={"Brands"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la marca",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Apple Inc.", description="Nombre de la marca"),
     *             @OA\Property(property="description", type="string", maxLength=1000, example="Empresa líder en tecnología e innovación", description="Descripción de la marca"),
     *             @OA\Property(property="logo_url", type="string", format="url", maxLength=500, example="https://example.com/new-logo.png", description="URL del logo de la marca"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Estado activo de la marca")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marca actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marca actualizada correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Brand")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marca no encontrada",
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
     * @OA\Delete(
     *     path="/api/v1/brands/{id}",
     *     summary="Eliminar marca",
     *     description="Elimina una marca existente (solo si no tiene productos asociados)",
     *     tags={"Brands"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la marca",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marca eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marca eliminada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marca no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar (tiene productos asociados)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se puede eliminar la marca porque tiene productos asociados")
     *         )
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
     * @OA\Get(
     *     path="/api/v1/brands/active/list",
     *     summary="Obtener marcas activas",
     *     description="Obtiene todas las marcas activas ordenadas por nombre",
     *     tags={"Brands"},
     *     @OA\Response(
     *         response=200,
     *         description="Marcas activas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marcas activas obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Brand")
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
     * @OA\Get(
     *     path="/api/v1/brands/slug/{slug}",
     *     summary="Obtener marca por slug",
     *     description="Obtiene una marca específica por su slug (solo marcas activas)",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug de la marca",
     *         required=true,
     *         @OA\Schema(type="string", example="apple")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marca obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marca obtenida correctamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Brand")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marca no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
