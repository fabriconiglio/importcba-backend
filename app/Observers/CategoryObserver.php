<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CategoryObserver
{
    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        // Solo optimizar si la imagen cambió y no es ya un WebP
        if ($category->wasChanged('image_url') && $category->image_url) {
            $this->optimizeImageIfNeeded($category);
        }
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        // Eliminar imagen física cuando se elimina la categoría
        if ($category->image_url) {
            Storage::disk('public')->delete($category->image_url);
        }
    }

    /**
     * Optimizar imagen si no está ya optimizada
     */
    private function optimizeImageIfNeeded(Category $category): void
    {
        if (!$category->image_url || str_ends_with($category->image_url, '.webp')) {
            return; // Ya optimizada o no hay imagen
        }

        try {
            $imageService = app(ImageService::class);
            $optimized = $imageService->optimizeExistingImage($category->image_url);
            
            if ($optimized) {
                // Actualizar ruta a WebP
                $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $category->image_url);
                
                if (Storage::disk('public')->exists($webpPath)) {
                    $category->update(['image_url' => $webpPath]);
                    Log::info("Imagen de categoría optimizada automáticamente: {$category->name}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error optimizando imagen de categoría {$category->name}: " . $e->getMessage());
        }
    }
}
