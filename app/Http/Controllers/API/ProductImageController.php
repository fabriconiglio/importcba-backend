<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Subir una o más imágenes para un producto
     */
    public function store(Request $request, string $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'images.*' => ['required', 'image', 'max:5120'], // 5MB máximo por imagen
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($productId);
            $uploadedImages = [];

            DB::beginTransaction();

            foreach ($request->file('images') as $image) {
                // Procesar y guardar imagen
                $urls = $this->imageService->store(
                    $image,
                    'products/' . $product->id,
                    $this->imageService->getProductImageSizes()
                );

                // Crear registro en la base de datos
                $productImage = $product->images()->create([
                    'url' => $urls['original'],
                    'thumbnail_url' => $urls['thumb'] ?? null,
                    'small_url' => $urls['small'] ?? null,
                    'medium_url' => $urls['medium'] ?? null,
                    'sort_order' => $product->images()->count() + 1,
                ]);

                $uploadedImages[] = [
                    'id' => $productImage->id,
                    'urls' => $urls,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imágenes subidas correctamente',
                'data' => $uploadedImages
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una imagen de producto
     */
    public function destroy(string $productId, string $imageId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $image = $product->images()->findOrFail($imageId);

            // Eliminar archivos físicos
            $path = str_replace('/storage/', '', parse_url($image->url, PHP_URL_PATH));
            $this->imageService->delete($path);

            // Eliminar registro de la base de datos
            $image->delete();

            // Reordenar imágenes restantes
            $product->images()
                ->orderBy('sort_order')
                ->get()
                ->each(function ($img, $index) {
                    $img->update(['sort_order' => $index + 1]);
                });

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el orden de las imágenes
     */
    public function updateOrder(Request $request, string $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'images' => ['required', 'array'],
                'images.*.id' => ['required', 'string', 'exists:product_images,id'],
                'images.*.sort_order' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($productId);

            DB::beginTransaction();

            foreach ($request->images as $imageData) {
                $product->images()
                    ->where('id', $imageData['id'])
                    ->update(['sort_order' => $imageData['sort_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Establecer imagen principal
     */
    public function setPrimary(string $productId, string $imageId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $image = $product->images()->findOrFail($imageId);

            // Actualizar todas las imágenes del producto
            $product->images()->update(['is_primary' => false]);
            
            // Establecer la imagen seleccionada como principal
            $image->update(['is_primary' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen principal actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar imagen principal: ' . $e->getMessage()
            ], 500);
        }
    }
}