<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageService
{

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
        $sourceImage = $this->createImageFromFile($file->getPathname());
        if (!$sourceImage) {
            throw new \Exception('No se pudo procesar la imagen');
        }

        // Redimensionar si es muy grande
        $resizedImage = $this->resizeImage($sourceImage, 1200, 1200);
        
        // Guardar como JPEG
        $tempPath = tempnam(sys_get_temp_dir(), 'jpg_');
        imagejpeg($resizedImage, $tempPath, 80);
        $disk->put($fullPath, file_get_contents($tempPath));
        unlink($tempPath);
        
        $urls = ['original' => Storage::url($fullPath)];

        // Procesar tamaños adicionales
        foreach ($sizes as $size => $dimensions) {
            $resizedPath = $path . '/' . $size . '_' . $fileName;
            $width = (int) ($dimensions['width'] ?? 300);
            $height = (int) ($dimensions['height'] ?? $width);
            
            // Crear versión redimensionada
            $sizedImage = $this->resizeImage($sourceImage, $width, $height);
            
            $tempPath = tempnam(sys_get_temp_dir(), 'jpg_');
            imagejpeg($sizedImage, $tempPath, 80);
            $disk->put($resizedPath, file_get_contents($tempPath));
            unlink($tempPath);
            
            imagedestroy($sizedImage);
            $urls[$size] = Storage::url($resizedPath);
        }

        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

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
     * Optimizar imagen para categorías (400x400, WebP, alta calidad)
     */
    public function optimizeForCategory(UploadedFile $file, string $directory = 'categories'): string
    {
        return $this->optimizeImageGD($file, $directory, 400, 400);
    }

    /**
     * Optimizar imagen para marcas (300x300, WebP, alta calidad)
     */
    public function optimizeForBrand(UploadedFile $file, string $directory = 'brands'): string
    {
        return $this->optimizeImageGD($file, $directory, 300, 300);
    }

    /**
     * Optimizar imagen usando GD nativo
     */
    private function optimizeImageGD(UploadedFile $file, string $directory, int $width, int $height): string
    {
        $fileName = Str::uuid() . '.webp';
        $fullPath = $directory . '/' . $fileName;
        $disk = Storage::disk('public');

        // Crear directorio si no existe
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        // Crear imagen desde el archivo
        $sourceImage = $this->createImageFromFile($file->getPathname());
        if (!$sourceImage) {
            throw new \Exception('No se pudo crear imagen desde el archivo');
        }

        // Redimensionar manteniendo aspecto ratio y crop al centro
        $optimizedImage = $this->resizeAndCrop($sourceImage, $width, $height);

        // Guardar como WebP
        $tempPath = tempnam(sys_get_temp_dir(), 'webp_');
        imagewebp($optimizedImage, $tempPath, 85);

        // Subir al storage
        $disk->put($fullPath, file_get_contents($tempPath));

        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);
        unlink($tempPath);

        return $fullPath;
    }

    /**
     * Optimizar imagen existente por ruta
     */
    public function optimizeExistingImage(string $filePath, int $maxWidth = 400, int $maxHeight = 400): bool
    {
        try {
            $disk = Storage::disk('public');
            
            if (!$disk->exists($filePath)) {
                return false;
            }

            $fullPath = $disk->path($filePath);
            $info = getimagesize($fullPath);
            
            if (!$info) {
                return false;
            }

            // Solo optimizar si es necesario
            if ($info[0] <= $maxWidth && $info[1] <= $maxHeight && str_ends_with($filePath, '.webp')) {
                return true; // Ya está optimizada
            }

            // Crear imagen desde archivo
            $sourceImage = $this->createImageFromFile($fullPath);
            if (!$sourceImage) {
                return false;
            }

            // Redimensionar
            $optimizedImage = $this->resizeAndCrop($sourceImage, $maxWidth, $maxHeight);

            // Crear nueva ruta WebP
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filePath);
            
            // Guardar como WebP
            $tempPath = tempnam(sys_get_temp_dir(), 'webp_');
            imagewebp($optimizedImage, $tempPath, 85);

            // Subir al storage
            $disk->put($webpPath, file_get_contents($tempPath));

            // Limpiar memoria
            imagedestroy($sourceImage);
            imagedestroy($optimizedImage);
            unlink($tempPath);

            // Si se creó un nuevo archivo WebP, eliminar el original
            if ($webpPath !== $filePath) {
                $disk->delete($filePath);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error optimizando imagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear imagen GD desde archivo
     */
    private function createImageFromFile(string $filePath)
    {
        $info = getimagesize($filePath);
        if (!$info) {
            return false;
        }

        switch ($info['mime']) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    /**
     * Redimensionar y hacer crop manteniendo aspecto
     */
    private function resizeAndCrop($sourceImage, int $targetWidth, int $targetHeight)
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Calcular dimensiones para mantener aspecto
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            // Imagen más ancha - crop horizontalmente
            $newHeight = $sourceHeight;
            $newWidth = $sourceHeight * $targetRatio;
            $cropX = ($sourceWidth - $newWidth) / 2;
            $cropY = 0;
        } else {
            // Imagen más alta - crop verticalmente
            $newWidth = $sourceWidth;
            $newHeight = $sourceWidth / $targetRatio;
            $cropX = 0;
            $cropY = ($sourceHeight - $newHeight) / 2;
        }

        // Crear imagen destino
        $destImage = imagecreatetruecolor($targetWidth, $targetHeight);

        // Preservar transparencia para PNG
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefill($destImage, 0, 0, $transparent);

        // Redimensionar y crop
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, $cropX, $cropY,
            $targetWidth, $targetHeight, $newWidth, $newHeight
        );

        return $destImage;
    }

    /**
     * Redimensionar imagen manteniendo proporción (sin crop)
     */
    private function resizeImage($sourceImage, int $maxWidth, int $maxHeight)
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Si ya es más pequeña, no redimensionar
        if ($sourceWidth <= $maxWidth && $sourceHeight <= $maxHeight) {
            $destImage = imagecreatetruecolor($sourceWidth, $sourceHeight);
            imagecopy($destImage, $sourceImage, 0, 0, 0, 0, $sourceWidth, $sourceHeight);
            return $destImage;
        }

        // Calcular nuevas dimensiones manteniendo aspecto
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = (int) ($sourceWidth * $ratio);
        $newHeight = (int) ($sourceHeight * $ratio);

        // Crear imagen redimensionada
        $destImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefill($destImage, 0, 0, $transparent);

        // Redimensionar
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $sourceWidth, $sourceHeight
        );

        return $destImage;
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