<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Banners",
 *     description="Endpoints para gestión de banners publicitarios"
 * )
 */
class BannerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/banners/public",
     *     summary="Listar banners públicos",
     *     description="Obtiene una lista de banners activos para mostrar en el sitio web",
     *     tags={"Banners"},
     *     @OA\Response(
     *         response=200,
     *         description="Banners obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Banners obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="MEGA DESCUENTO"),
     *                     @OA\Property(property="description", type="string", example="En utensilios de importadora"),
                     *                     @OA\Property(property="image_url", type="string", example="banners/desktop/banner-mega-descuento.jpg"),
                     *                     @OA\Property(property="mobile_image_url", type="string", example="banners/mobile/banner-mega-descuento-mobile.jpg"),
     *                     @OA\Property(property="link_url", type="string", example="/catalogo?featured=true"),
     *                     @OA\Property(property="link_text", type="string", example="Ver Ofertas"),
     *                     @OA\Property(property="sort_order", type="integer", example=1)
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
    public function publicIndex(): JsonResponse
    {
        try {
            $banners = Banner::active()
                ->ordered()
                ->get();

            $transformedBanners = $banners->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'image_url' => $banner->image_url,
                    'mobile_image_url' => $banner->mobile_image_url,
                    'link_url' => $banner->link_url,
                    'link_text' => $banner->link_text,
                    'sort_order' => $banner->sort_order,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedBanners,
                'message' => 'Banners obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener banners: ' . $e->getMessage()
            ], 500);
        }
    }
}
