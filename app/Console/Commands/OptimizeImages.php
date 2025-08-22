<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OptimizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize 
                            {--dry-run : Solo mostrar qué se optimizaría}
                            {--force : Forzar reoptimización de imágenes ya optimizadas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimiza todas las imágenes de categorías y marcas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🖼️  Iniciando optimización de imágenes...');
        
        $imageService = app(ImageService::class);
        $directories = ['categories', 'brands'];
        $totalOptimized = 0;
        $totalSaved = 0;

        foreach ($directories as $directory) {
            $this->info("📁 Procesando directorio: {$directory}");
            
            $disk = Storage::disk('public');
            
            if (!$disk->exists($directory)) {
                $this->warn("⚠️  Directorio {$directory} no existe");
                continue;
            }

            $files = $disk->files($directory);
            $imageFiles = collect($files)->filter(function ($file) {
                return preg_match('/\.(jpg|jpeg|png|webp)$/i', $file);
            });
            
            foreach ($imageFiles as $file) {
                $filename = basename($file);
                $fullPath = $disk->path($file);
                $originalSize = filesize($fullPath);
                
                // Saltar archivos ya optimizados a menos que se use --force
                if (str_ends_with($file, '.webp') && !$this->option('force')) {
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("  📋 Se optimizaría: {$filename} (" . $this->formatBytes($originalSize) . ")");
                    continue;
                }

                $optimized = $imageService->optimizeExistingImage($file);
                
                if ($optimized) {
                    // Calcular nuevo tamaño (buscar archivo WebP correspondiente)
                    $webpFile = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
                    $newPath = $disk->path($webpFile);
                    
                    if (file_exists($newPath)) {
                        $newSize = filesize($newPath);
                        $saved = $originalSize - $newSize;
                        $totalSaved += $saved;
                        $totalOptimized++;
                        
                        $percentage = round(($saved / $originalSize) * 100, 1);
                        $this->line("  ✅ {$filename}: " . $this->formatBytes($originalSize) . " → " . $this->formatBytes($newSize) . " (-{$percentage}%)");
                        
                        // Actualizar referencias en la base de datos
                        $this->updateDatabaseReferences($filename, basename($webpFile));
                    }
                } else {
                    $this->line("  ❌ Error optimizando: {$filename}");
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info("\n🔍 Modo dry-run - No se optimizó ninguna imagen");
        } else {
            $this->info("\n✨ Optimización completada!");
            $this->info("📊 Imágenes optimizadas: {$totalOptimized}");
            $this->info("💾 Espacio ahorrado: " . $this->formatBytes($totalSaved));
        }

        return 0;
    }



    private function updateDatabaseReferences(string $oldFilename, string $newFilename): void
    {
        // Actualizar referencias en categorías
        $categories = DB::table('categories')
            ->where('image_url', 'like', "%{$oldFilename}")
            ->get();
            
        foreach ($categories as $category) {
            $newImageUrl = str_replace($oldFilename, $newFilename, $category->image_url);
            DB::table('categories')
                ->where('id', $category->id)
                ->update(['image_url' => $newImageUrl]);
        }
            
        // Actualizar referencias en marcas
        $brands = DB::table('brands')
            ->where('logo_url', 'like', "%{$oldFilename}")
            ->get();
            
        foreach ($brands as $brand) {
            $newLogoUrl = str_replace($oldFilename, $newFilename, $brand->logo_url);
            DB::table('brands')
                ->where('id', $brand->id)
                ->update(['logo_url' => $newLogoUrl]);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
