<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

class FixImageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:fix-urls {--dry-run : Only show what would be changed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix image URLs that don\'t match actual files (e.g., .jpg in DB but .webp file exists)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $fixed = 0;

        // Fix Banners
        $this->info('🖼️  Checking Banners...');
        foreach (Banner::whereNotNull('image_url')->get() as $banner) {
            $fixedUrl = $this->checkAndFixImageUrl($banner->image_url, 'banners');
            if ($fixedUrl && $fixedUrl !== $banner->image_url) {
                if (!$dryRun) {
                    $banner->updateQuietly(['image_url' => $fixedUrl]);
                }
                $this->line("  ✅ {$banner->title}: {$banner->image_url} → {$fixedUrl}");
                $fixed++;
            }
        }

        // Fix Categories
        $this->info('📁 Checking Categories...');
        foreach (Category::whereNotNull('image_url')->get() as $category) {
            $fixedUrl = $this->checkAndFixImageUrl($category->image_url, 'categories');
            if ($fixedUrl && $fixedUrl !== $category->image_url) {
                if (!$dryRun) {
                    $category->updateQuietly(['image_url' => $fixedUrl]);
                }
                $this->line("  ✅ {$category->name}: {$category->image_url} → {$fixedUrl}");
                $fixed++;
            }
        }

        // Fix Brands
        $this->info('🏷️  Checking Brands...');
        foreach (Brand::whereNotNull('logo_url')->get() as $brand) {
            $fixedUrl = $this->checkAndFixImageUrl($brand->logo_url, 'brands');
            if ($fixedUrl && $fixedUrl !== $brand->logo_url) {
                if (!$dryRun) {
                    $brand->updateQuietly(['logo_url' => $fixedUrl]);
                }
                $this->line("  ✅ {$brand->name}: {$brand->logo_url} → {$fixedUrl}");
                $fixed++;
            }
        }

        $this->newLine();
        if ($fixed > 0) {
            if ($dryRun) {
                $this->warn("🔍 Found {$fixed} image URLs that need fixing.");
                $this->info("Run without --dry-run to apply changes.");
            } else {
                $this->success("✅ Fixed {$fixed} image URLs successfully!");
            }
        } else {
            $this->success("✅ All image URLs are correct!");
        }
    }

    /**
     * Check if an image URL needs fixing and return the correct URL
     */
    private function checkAndFixImageUrl(string $imageUrl, string $directory): ?string
    {
        // Si el archivo existe tal como está, no hay problema
        if (Storage::disk('public')->exists($imageUrl)) {
            return null;
        }

        // Verificar si existe una versión WebP
        $webpUrl = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $imageUrl);
        if ($webpUrl !== $imageUrl && Storage::disk('public')->exists($webpUrl)) {
            return $webpUrl;
        }

        // Verificar otros formatos posibles
        $baseUrl = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $imageUrl);
        $extensions = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
        
        foreach ($extensions as $ext) {
            $testUrl = $baseUrl . '.' . $ext;
            if (Storage::disk('public')->exists($testUrl)) {
                return $testUrl;
            }
        }

        // No se encontró ningún archivo
        $this->warn("  ⚠️  File not found: {$imageUrl}");
        return null;
    }
}