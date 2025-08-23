<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductImageObserver
{
    /**
     * Handle the ProductImage "created" event.
     */
    public function created(ProductImage $productImage): void
    {
        // Si se marca como principal, desmarcar las demás
        if ($productImage->is_primary) {
            $productImage->product->images()
                ->where('id', '!=', $productImage->id)
                ->update(['is_primary' => false]);
        }

        // Si no tiene orden, asignar el siguiente disponible
        if (is_null($productImage->sort_order)) {
            $maxOrder = $productImage->product->images()
                ->where('id', '!=', $productImage->id)
                ->max('sort_order') ?? 0;
            $productImage->update(['sort_order' => $maxOrder + 1]);
        }

        // Optimizar imagen
        if ($productImage->url) {
            $this->optimizeImageIfNeeded($productImage);
        }
    }

    /**
     * Handle the ProductImage "updated" event.
     */
    public function updated(ProductImage $productImage): void
    {
        // Si se marcó como principal, desmarcar las demás
        if ($productImage->wasChanged('is_primary') && $productImage->is_primary) {
            $productImage->product->images()
                ->where('id', '!=', $productImage->id)
                ->update(['is_primary' => false]);
        }

        // Si la URL cambió, optimizar imagen
        if ($productImage->wasChanged('url')) {
            $this->optimizeImageIfNeeded($productImage);
        }
    }

    /**
     * Handle the ProductImage "deleted" event.
     */
    public function deleted(ProductImage $productImage): void
    {
        // Eliminar archivo físico
        if ($productImage->url && Storage::disk('public')->exists($productImage->url)) {
            try {
                Storage::disk('public')->delete($productImage->url);
                Log::info("Product image deleted: {$productImage->url}");
            } catch (\Exception $e) {
                Log::error("Error deleting product image: " . $e->getMessage());
            }
        }

        // Si era la imagen principal, asignar la primera disponible como principal
        if ($productImage->is_primary) {
            $firstImage = $productImage->product->images()
                ->orderBy('sort_order')
                ->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }
    }

    /**
     * Optimizar imagen si es necesario
     */
    private function optimizeImageIfNeeded(ProductImage $productImage): void
    {
        if (!$productImage->url) {
            return;
        }

        try {
            $originalUrl = $productImage->url;
            $imageService = app(ImageService::class);
            
            // Optimizar imagen específicamente para productos (800x800)
            $optimized = $imageService->optimizeExistingImage(
                $productImage->url,
                800, // ancho
                800, // alto
                'products'
            );

            if ($optimized) {
                // Verificar si se creó un archivo WebP
                $webpUrl = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $originalUrl);
                
                if ($webpUrl !== $originalUrl && Storage::disk('public')->exists($webpUrl)) {
                    // Actualizar la URL en la base de datos sin disparar eventos
                    $productImage->updateQuietly(['url' => $webpUrl]);
                    Log::info("Product image URL updated to WebP: {$webpUrl}");
                }
                
                Log::info("Product image optimized successfully: {$productImage->url}");
            }
        } catch (\Exception $e) {
            Log::error("Error optimizing product image {$productImage->id}: " . $e->getMessage());
        }
    }
} 