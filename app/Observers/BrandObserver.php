<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BrandObserver
{
    /**
     * Handle the Brand "created" event.
     */
    public function created(Brand $brand): void
    {
        if ($brand->logo_url) {
            $this->optimizeImageIfNeeded($brand);
        }
    }

    /**
     * Handle the Brand "updated" event.
     */
    public function updated(Brand $brand): void
    {
        // Verificar si la imagen cambió
        if ($brand->wasChanged('logo_url')) {
            $this->optimizeImageIfNeeded($brand);
        }
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        // Eliminar archivo físico de imagen cuando se elimina la marca
        if ($brand->logo_url && Storage::disk('public')->exists($brand->logo_url)) {
            try {
                Storage::disk('public')->delete($brand->logo_url);
            } catch (\Exception $e) {
                Log::error("Error eliminando logo de marca: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Brand "deleting" event.
     * IMPORTANTE: Este método se ejecuta ANTES de eliminar la marca
     */
    public function deleting(Brand $brand): void
    {
        // Opción 1: Eliminar todos los productos de esta marca
        // $brand->products()->delete();
        
        // Opción 2: Desasociar productos de la marca (más seguro)
        $brand->products()->update(['brand_id' => null]);
        
        // Opción 3: Desactivar productos en lugar de eliminarlos
        // $brand->products()->update(['is_active' => false]);
        
        // Eliminar relaciones many-to-many con categorías
        $brand->categories()->detach();
    }

    /**
     * Optimizar imagen si es necesario
     */
    private function optimizeImageIfNeeded(Brand $brand): void
    {
        if (!$brand->logo_url) {
            return;
        }

        try {
            $originalUrl = $brand->logo_url;
            $imageService = app(ImageService::class);
            
            // Optimizar imagen específicamente para logos de marcas
            $optimized = $imageService->optimizeExistingImage(
                $brand->logo_url,
                300, // ancho
                150, // alto
                'brands'
            );

            if ($optimized) {
                // Verificar si se creó un archivo WebP
                $webpUrl = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $originalUrl);
                
                if ($webpUrl !== $originalUrl && Storage::disk('public')->exists($webpUrl)) {
                    // Actualizar la URL en la base de datos sin disparar eventos
                    $brand->updateQuietly(['logo_url' => $webpUrl]);
                }
                
            }
        } catch (\Exception $e) {
            Log::error("Error optimizando logo de marca {$brand->name}: " . $e->getMessage());
        }
    }
}