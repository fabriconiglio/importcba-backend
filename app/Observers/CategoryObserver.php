<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        if ($category->image_url) {
            $this->optimizeImageIfNeeded($category);
        }
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        // Verificar si la imagen cambió
        if ($category->wasChanged('image_url')) {
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
     * Optimizar imagen si es necesario
     */
    private function optimizeImageIfNeeded(Category $category): void
    {
        if (!$category->image_url) {
            return;
        }

        try {
            $originalUrl = $category->image_url;
            $imageService = app(ImageService::class);
            
            // Optimizar imagen específicamente para categorías
            $optimized = $imageService->optimizeExistingImage(
                $category->image_url,
                400, // ancho
                400, // alto
                'categories'
            );

            if ($optimized) {
                // Verificar si se creó un archivo WebP
                $webpUrl = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $originalUrl);
                
                if ($webpUrl !== $originalUrl && Storage::disk('public')->exists($webpUrl)) {
                    // Actualizar la URL en la base de datos sin disparar eventos
                    $category->updateQuietly(['image_url' => $webpUrl]);
                }
                
            }
        } catch (\Exception $e) {
            Log::error("Error optimizando imagen de categoría {$category->name}: " . $e->getMessage());
        }
    }
}
