<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class OptimizeSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:optimize {--force : Force optimization without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the entire system for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('¿Estás seguro de que quieres optimizar el sistema? Esto puede tomar varios minutos.')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->info('🚀 Iniciando optimización del sistema...');

        // 1. Limpiar caché
        $this->info('📦 Limpiando caché...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // 2. Limpiar sesiones antiguas
        $this->info('🧹 Limpiando sesiones antiguas...');
        Artisan::call('sessions:cleanup', ['--days' => 7]);

        // 3. Optimizar autoloader
        $this->info('⚡ Optimizando autoloader...');
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');

        // 4. Limpiar logs antiguos
        $this->info('📝 Limpiando logs antiguos...');
        $this->cleanOldLogs();

        // 5. Verificar permisos de storage
        $this->info('🔐 Verificando permisos de storage...');
        $this->checkStoragePermissions();

        $this->info('✅ Optimización del sistema completada exitosamente!');
        $this->info('💡 Recomendaciones:');
        $this->info('   - Reinicia el servidor web si es posible');
        $this->info('   - Monitorea el rendimiento de los formularios');
        $this->info('   - Ejecuta este comando semanalmente para mantener el rendimiento');

        return 0;
    }

    /**
     * Clean old log files
     */
    private function cleanOldLogs(): void
    {
        $logsPath = storage_path('logs');
        $cutoff = now()->subDays(30);
        $deletedCount = 0;

        if (is_dir($logsPath)) {
            $files = glob($logsPath . '/*.log');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff->timestamp) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }

        $this->info("   Limpiados {$deletedCount} archivos de log antiguos.");
    }

    /**
     * Check and fix storage permissions
     */
    private function checkStoragePermissions(): void
    {
        $paths = [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            
            if (!is_writable($path)) {
                chmod($path, 0755);
                $this->warn("   Permisos corregidos para: {$path}");
            }
        }
    }
} 