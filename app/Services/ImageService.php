<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Str;

class ImageService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        // Inicializa el ImageManager con el driver GD (disponible por defecto)
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Procesar y guardar una imagen
     */
    public function store(UploadedFile $file, string $path = 'products', array $sizes = []): array
    {
        // Generar nombre único para la imagen
        $extension = 'jpg';
        $fileName = Str::uuid() . '.' . $extension;
        $fullPath = $path . '/' . $fileName;

        // Usar el disco público
        $disk = Storage::disk('public');

        // Crear directorio si no existe
        if (!$disk->exists($path)) {
            $disk->makeDirectory($path);
        }

        // Procesar imagen original
        $image = $this->imageManager
            ->read($file->getPathname())
            ->scaleDown(1200)
            ->toJpeg(80);

        // Guardar imagen original
        $disk->put($fullPath, (string) $image);
        $urls = ['original' => $disk->url($fullPath)];

        // Procesar tamaños adicionales
        foreach ($sizes as $size => $dimensions) {
            $resizedPath = $path . '/' . $size . '_' . $fileName;
            
            // Crear versión redimensionada
            $resizedImage = $this->imageManager
                ->read($file->getPathname())
                ->scaleDown((int) ($dimensions['width'] ?? 300))
                ->toJpeg(80);

            // Guardar versión redimensionada
            $disk->put($resizedPath, (string) $resizedImage);
            $urls[$size] = $disk->url($resizedPath);
        }

        return $urls;
    }

    /**
     * Eliminar una imagen y sus variantes
     */
    public function delete(string $path): bool
    {
        try {
            // Obtener información del archivo
            $directory = dirname($path);
            $fileName = basename($path);
            
            // Eliminar todas las variantes
            $files = Storage::disk('public')->files($directory);
            foreach ($files as $file) {
                if (Str::contains($file, $fileName)) {
                    Storage::disk('public')->delete($file);
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener las dimensiones predefinidas para productos
     */
    public function getProductImageSizes(): array
    {
        return [
            'thumb' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300],
            'medium' => ['width' => 600],
        ];
    }
}