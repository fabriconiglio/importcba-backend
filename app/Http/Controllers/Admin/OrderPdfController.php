<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class OrderPdfController extends Controller
{
    /**
     * Generar y descargar PDF del pedido
     */
    public function download(Order $order): Response|JsonResponse
    {
        try {
            // Cargar el pedido con sus relaciones
            $order->load(['user', 'items.product']);
            
            // Generar el PDF
            $pdf = Pdf::loadView('exports.order-pdf', compact('order'));
            
            // Configurar el PDF
            $pdf->setPaper('A4', 'portrait');
            
            // Nombre del archivo
            $fileName = 'pedido-' . $order->order_number . '.pdf';
            
            // Retornar el PDF como descarga
            return $pdf->download($fileName);
            
        } catch (\Exception $e) {
            // En caso de error, retornar una respuesta con error
            return response()->json([
                'error' => 'No se pudo generar el PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}