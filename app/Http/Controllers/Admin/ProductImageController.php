<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Subir imagen para un producto
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Si se marca como principal, desmarcar las demás
            if ($request->boolean('is_primary')) {
                $product->images()->update(['is_primary' => false]);
            }

            // Procesar y guardar la imagen
            $imageFile = $request->file('image');
            $sizes = $this->imageService->getProductImageSizes();
            $urls = $this->imageService->store($imageFile, 'products', $sizes);

            // Obtener el siguiente orden
            $nextOrder = $product->images()->max('sort_order') + 1;

            // Crear el registro de imagen
            $productImage = $product->images()->create([
                'url' => $urls['original'],
                'thumbnail_url' => $urls['thumb'] ?? null,
                'small_url' => $urls['small'] ?? null,
                'medium_url' => $urls['medium'] ?? null,
                'alt_text' => $request->input('alt_text'),
                'is_primary' => $request->boolean('is_primary'),
                'sort_order' => $nextOrder,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => $productImage->load('product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar imagen
     */
    public function update(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Si se marca como principal, desmarcar las demás
            if ($request->boolean('is_primary')) {
                $product->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);
            }

            $image->update([
                'alt_text' => $request->input('alt_text'),
                'is_primary' => $request->boolean('is_primary'),
                'sort_order' => $request->input('sort_order', $image->sort_order),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imagen actualizada correctamente',
                'data' => $image->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar imagen
     */
    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Eliminar archivos físicos
            $this->imageService->delete($image->url);

            // Eliminar registro
            $image->delete();

            // Si era la imagen principal, asignar la primera disponible como principal
            if ($image->is_primary) {
                $firstImage = $product->images()->orderBy('sort_order')->first();
                if ($firstImage) {
                    $firstImage->update(['is_primary' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reordenar imágenes
     */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:product_images,id',
            'images.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->input('images') as $imageData) {
                $product->images()
                    ->where('id', $imageData['id'])
                    ->update(['sort_order' => $imageData['sort_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden de imágenes actualizado correctamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar las imágenes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Establecer imagen como principal
     */
    public function setPrimary(Product $product, ProductImage $image): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Desmarcar todas las imágenes como principales
            $product->images()->update(['is_primary' => false]);

            // Marcar la imagen seleccionada como principal
            $image->update(['is_primary' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imagen principal establecida correctamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al establecer la imagen principal: ' . $e->getMessage(),
            ], 500);
        }
    }
} 