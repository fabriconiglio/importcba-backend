<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

/**
 * MOD-101 (main): Comando para limpiar productos de prueba en producción
 */
class CleanTestProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:clean-test {--force : Forzar eliminación sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina productos de prueba de la base de datos en producción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Solo ejecutar en producción
        if (app()->environment() !== 'production' && !$this->option('force')) {
            $this->info('Este comando solo se ejecuta en entorno de producción. Use --force para ejecutar en otros entornos.');
            return;
        }

        $this->info('🔍 Buscando productos de prueba...');

        // Buscar productos de prueba por diferentes criterios
        $testProducts = Product::where(function ($query) {
            $query->where('name', 'LIKE', '%prueba%')
                  ->orWhere('name', 'LIKE', '%test%')
                  ->orWhere('name', 'LIKE', '%mock%')
                  ->orWhere('name', 'LIKE', '%Producto de prueba%')
                  ->orWhere('description', 'LIKE', '%Producto de alta calidad.%')
                  ->orWhere('sku', 'LIKE', 'MOCK%')
                  ->orWhere('sku', 'LIKE', 'TEST%');
        })->get();

        $count = $testProducts->count();

        if ($count === 0) {
            $this->info('✅ No se encontraron productos de prueba.');
            return;
        }

        $this->warn("⚠️  Se encontraron {$count} productos de prueba:");

        // Mostrar lista de productos que se van a eliminar
        $testProducts->each(function ($product) {
            $this->line("  - ID: {$product->id} | SKU: {$product->sku} | Nombre: {$product->name}");
        });

        if (!$this->option('force') && !$this->confirm('¿Desea eliminar estos productos?')) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->info('🗑️  Eliminando productos de prueba...');

        $deleted = 0;
        $testProducts->each(function ($product) use (&$deleted) {
            try {
                $product->delete();
                $deleted++;
            } catch (\Exception $e) {
                $this->error("Error eliminando producto {$product->id}: {$e->getMessage()}");
            }
        });

        $this->info("✅ Se eliminaron {$deleted} productos de prueba exitosamente.");

        // Limpiar cache después de la eliminación
        $this->call('cache:clear');
        $this->info('🧹 Cache limpiado.');
    }
}
