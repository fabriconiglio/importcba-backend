<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\VolumeDiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Descuentos por Volumen",
 *     description="Endpoints para descuentos automáticos por volumen de compra"
 * )
 */
class VolumeDiscountController extends Controller
{
    protected VolumeDiscountService $volumeDiscountService;

    public function __construct(VolumeDiscountService $volumeDiscountService)
    {
        $this->volumeDiscountService = $volumeDiscountService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/volume-discounts/tiers",
     *     summary="Obtener niveles de descuento por volumen",
     *     description="Obtiene todos los niveles de descuento disponibles por volumen de compra",
     *     tags={"Descuentos por Volumen"},
     *     @OA\Response(
     *         response=200,
     *         description="Niveles de descuento obtenidos correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="min_amount", type="number", example=300000),
     *                 @OA\Property(property="percentage", type="integer", example=10),
     *                 @OA\Property(property="description", type="string", example="Superando $300.000")
     *             )),
     *             @OA\Property(property="message", type="string", example="Niveles de descuento obtenidos correctamente")
     *         )
     *     )
     * )
     */
    public function getTiers(): JsonResponse
    {
        try {
            $tiers = $this->volumeDiscountService->getDiscountTiers();

            return response()->json([
                'success' => true,
                'data' => $tiers,
                'message' => 'Niveles de descuento obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener niveles de descuento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/volume-discounts/calculate",
     *     summary="Calcular descuento por volumen",
     *     description="Calcula el descuento por volumen basado en el subtotal de la compra",
     *     tags={"Descuentos por Volumen"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="subtotal", type="number", example=350000, description="Subtotal de la compra")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Descuento calculado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="has_discount", type="boolean", example=true),
     *                 @OA\Property(property="percentage", type="integer", example=10),
     *                 @OA\Property(property="amount", type="number", example=35000),
     *                 @OA\Property(property="subtotal", type="number", example=350000),
     *                 @OA\Property(property="final_amount", type="number", example=315000),
     *                 @OA\Property(property="next_tier", type="object",
     *                     @OA\Property(property="amount", type="number", example=500000),
     *                     @OA\Property(property="percentage", type="integer", example=15),
     *                     @OA\Property(property="remaining", type="number", example=150000)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Descuento calculado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'subtotal' => 'required|numeric|min:0'
            ]);

            $subtotal = (float) $request->subtotal;
            $discount = $this->volumeDiscountService->getVolumeDiscount($subtotal);

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'Descuento calculado correctamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular descuento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/volume-discounts/progress",
     *     summary="Obtener progreso hacia siguiente nivel",
     *     description="Calcula cuánto falta para alcanzar el siguiente nivel de descuento",
     *     tags={"Descuentos por Volumen"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="subtotal", type="number", example=250000, description="Subtotal actual de la compra")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Progreso calculado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_amount", type="number", example=250000),
     *                 @OA\Property(property="next_tier_amount", type="number", example=300000),
     *                 @OA\Property(property="next_tier_percentage", type="integer", example=10),
     *                 @OA\Property(property="remaining", type="number", example=50000),
     *                 @OA\Property(property="progress_percentage", type="number", example=83.33)
     *             ),
     *             @OA\Property(property="message", type="string", example="Progreso calculado correctamente")
     *         )
     *     )
     * )
     */
    public function getProgress(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'subtotal' => 'required|numeric|min:0'
            ]);

            $subtotal = (float) $request->subtotal;
            $progress = $this->volumeDiscountService->getNextTierProgress($subtotal);

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Progreso calculado correctamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular progreso: ' . $e->getMessage()
            ], 500);
        }
    }
} 