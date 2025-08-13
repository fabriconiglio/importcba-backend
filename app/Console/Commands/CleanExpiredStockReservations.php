<?php

namespace App\Console\Commands;

use App\Services\StockReservationService;
use Illuminate\Console\Command;

class CleanExpiredStockReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:clean-expired {--dry-run : Mostrar qué se limpiaría sin ejecutar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar reservas de stock expiradas';

    private StockReservationService $stockReservationService;

    public function __construct(StockReservationService $stockReservationService)
    {
        parent::__construct();
        $this->stockReservationService = $stockReservationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de reservas de stock expiradas...');

        if ($this->option('dry-run')) {
            $this->warn('🔍 Modo DRY RUN - No se ejecutarán cambios');
            
            // Contar reservas expiradas
            $expiredCount = \App\Models\StockReservation::expired()
                ->where('status', 'pending')
                ->count();
            
            $this->info("📊 Se encontrarían {$expiredCount} reservas expiradas para limpiar");
            
            if ($expiredCount > 0) {
                $this->table(
                    ['ID', 'Producto', 'Cantidad', 'Usuario', 'Expira'],
                    \App\Models\StockReservation::expired()
                        ->where('status', 'pending')
                        ->with(['product', 'user'])
                        ->limit(10)
                        ->get()
                        ->map(function ($reservation) {
                            return [
                                $reservation->id,
                                $reservation->product?->name ?? 'N/A',
                                $reservation->quantity,
                                $reservation->user?->email ?? 'Anónimo',
                                $reservation->expires_at->format('Y-m-d H:i:s'),
                            ];
                        })
                );
            }
            
            return 0;
        }

        $startTime = microtime(true);
        
        try {
            $result = $this->stockReservationService->cleanExpiredReservations();
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            if ($result['success']) {
                $cleanedCount = $result['data']['cleaned_reservations'];
                
                if ($cleanedCount > 0) {
                    $this->info("✅ Se limpiaron {$cleanedCount} reservas expiradas");
                    $this->info("⏱️  Tiempo de ejecución: {$executionTime} segundos");
                } else {
                    $this->info("✅ No hay reservas expiradas para limpiar");
                }
                
                return 0;
            } else {
                $this->error("❌ Error al limpiar reservas: " . $result['message']);
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
