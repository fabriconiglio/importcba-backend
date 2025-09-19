<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// MOD-005 (main): Controlador para probar envío de emails
class EmailTestController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Probar configuración de email
     */
    public function testConfig(): JsonResponse
    {
        $config = $this->emailService->checkEmailConfiguration();
        
        return response()->json($config);
    }

    /**
     * Probar conexión con Brevo API
     */
    public function testBrevo(): JsonResponse
    {
        $result = $this->emailService->testBrevoApi();
        
        return response()->json($result);
    }

    /**
     * Reenviar email de confirmación de un pedido específico
     */
    public function resendOrderEmail(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id'
            ]);

            $order = Order::with(['user', 'items'])->findOrFail($request->order_id);
            
            $result = $this->emailService->sendOrderConfirmation($order);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de prueba
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'name' => 'required|string'
            ]);

            $result = $this->emailService->sendSimpleEmailViaApi(
                $request->email,
                $request->name,
                'Email de Prueba - ' . config('app.name'),
                'Este es un email de prueba para verificar que la configuración funciona correctamente.'
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
