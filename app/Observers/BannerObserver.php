<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BannerObserver
{
    /**
     * Handle the Banner "created" event.
     */
    public function created(Banner $banner): void
    {
        if ($banner->image_url) {
            $this->optimizeImageIfNeeded($banner);
        }
    }

    /**
     * Handle the Banner "updated" event.
     */
    public function updated(Banner $banner): void
    {
        // Verificar si la imagen cambió
        if ($banner->wasChanged('image_url')) {
            $this->optimizeImageIfNeeded($banner);
        }
    }

    /**
     * Handle the Banner "deleted" event.
     */
    public function deleted(Banner $banner): void
    {
        // Eliminar archivo físico de imagen cuando se elimina el banner
        if ($banner->image_url && Storage::disk('public')->exists($banner->image_url)) {
            try {
                Storage::disk('public')->delete($banner->image_url);
                Log::info("Imagen de banner eliminada: {$banner->image_url}");
            } catch (\Exception $e) {
                Log::error("Error eliminando imagen de banner: " . $e->getMessage());
            }
        }
    }

    /**
     * Optimizar imagen si es necesario
     */
    private function optimizeImageIfNeeded(Banner $banner): void
    {
        if (!$banner->image_url) {
            return;
        }

        try {
            $originalUrl = $banner->image_url;
            $imageService = app(ImageService::class);
            
            // Optimizar imagen específicamente para banners (1200x675 - 16:9)
            $optimized = $imageService->optimizeExistingImage(
                $banner->image_url,
                1200, // ancho
                675,  // alto
                'banners'
            );

            if ($optimized) {
                // Verificar si se creó un archivo WebP
                $webpUrl = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $originalUrl);
                
                if ($webpUrl !== $originalUrl && Storage::disk('public')->exists($webpUrl)) {
                    // Actualizar la URL en la base de datos sin disparar eventos
                    $banner->updateQuietly(['image_url' => $webpUrl]);
                    Log::info("Banner image URL updated to WebP: {$webpUrl}");
                }
                
                Log::info("Banner image optimized successfully: {$banner->image_url}");
            }
        } catch (\Exception $e) {
            Log::error("Error optimizando imagen de banner {$banner->title}: " . $e->getMessage());
        }
    }
}
