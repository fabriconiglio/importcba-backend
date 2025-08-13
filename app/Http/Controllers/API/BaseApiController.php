<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     title="Ecommerce API",
 *     version="1.0.0",
 *     description="API completa para el sistema de ecommerce con autenticación, productos, carrito, pedidos, pagos, cupones y más.",
 *     @OA\Contact(
 *         email="soporte@ecommerce.com",
 *         name="Soporte Técnico"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para registro, login y gestión de usuarios"
 * )
 * 
 * @OA\Tag(
 *     name="Productos",
 *     description="Endpoints para gestión de productos, categorías y marcas"
 * )
 * 
 * @OA\Tag(
 *     name="Catálogo",
 *     description="Endpoints para búsqueda y filtrado de productos"
 * )
 * 
 * @OA\Tag(
 *     name="Carrito",
 *     description="Endpoints para gestión del carrito de compras"
 * )
 * 
 * @OA\Tag(
 *     name="Pedidos",
 *     description="Endpoints para gestión de pedidos y checkout"
 * )
 * 
 * @OA\Tag(
 *     name="Pagos",
 *     description="Endpoints para procesamiento de pagos"
 * )
 * 
 * @OA\Tag(
 *     name="Cupones",
 *     description="Endpoints para gestión de cupones y descuentos"
 * )
 * 
 * @OA\Tag(
 *     name="Emails",
 *     description="Endpoints para envío de emails"
 * )
 * 
 * @OA\Tag(
 *     name="Stock",
 *     description="Endpoints para gestión de reservas de stock"
 * )
 */
class BaseApiController extends Controller
{
    /**
     * Respuesta de éxito estándar
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Respuesta de error estándar
     */
    protected function errorResponse(string $message = 'Error en la operación', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta de validación estándar
     */
    protected function validationErrorResponse($errors, string $message = 'Error de validación'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Respuesta de recurso no encontrado
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Respuesta de acceso denegado
     */
    protected function forbiddenResponse(string $message = 'Acceso denegado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 403);
    }

    /**
     * Respuesta de error interno del servidor
     */
    protected function serverErrorResponse(string $message = 'Error interno del servidor'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 500);
    }

    /**
     * Respuesta de recurso creado
     */
    protected function createdResponse($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }

    /**
     * Respuesta de recurso actualizado
     */
    protected function updatedResponse($data = null, string $message = 'Recurso actualizado exitosamente'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    /**
     * Respuesta de recurso eliminado
     */
    protected function deletedResponse(string $message = 'Recurso eliminado exitosamente'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], 200);
    }

    /**
     * Respuesta paginada
     */
    protected function paginatedResponse($data, string $message = 'Datos obtenidos exitosamente'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages(),
            ]
        ], 200);
    }
} 