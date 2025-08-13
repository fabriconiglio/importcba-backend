<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Enviar email de confirmación de pedido
     */
    public function sendOrderConfirmation(Request $request, string $orderId): JsonResponse
    {
        try {
            $order = Order::with(['user', 'items'])->findOrFail($orderId);
            
            // Verificar que el usuario autenticado es el propietario del pedido
            if ($request->user()->id !== $order->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para enviar este email'
                ], 403);
            }

            $result = $this->emailService->sendOrderConfirmation($order);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de confirmación de pedido en cola
     */
    public function queueOrderConfirmation(Request $request, string $orderId): JsonResponse
    {
        try {
            $order = Order::with(['user', 'items'])->findOrFail($orderId);
            
            // Verificar que el usuario autenticado es el propietario del pedido
            if ($request->user()->id !== $order->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para enviar este email'
                ], 403);
            }

            $result = $this->emailService->queueOrderConfirmation($order);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al encolar email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de recuperación de contraseña
     */
    public function sendPasswordReset(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();
            
            // Generar token de recuperación
            $token = \Illuminate\Support\Str::random(60);
            
            // Guardar token en la base de datos (puedes usar password_resets table)
            \Illuminate\Support\Facades\DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            $result = $this->emailService->sendPasswordReset($user, $token);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar email de recuperación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de recuperación de contraseña en cola
     */
    public function queuePasswordReset(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();
            
            // Generar token de recuperación
            $token = \Illuminate\Support\Str::random(60);
            
            // Guardar token en la base de datos
            \Illuminate\Support\Facades\DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            $result = $this->emailService->queuePasswordReset($user, $token);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al encolar email de recuperación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de bienvenida
     */
    public function sendWelcome(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            // Solo administradores pueden enviar emails de bienvenida
            if (!$request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $result = $this->emailService->sendWelcome($user);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar email de bienvenida: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de bienvenida en cola
     */
    public function queueWelcome(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            // Solo administradores pueden enviar emails de bienvenida
            if (!$request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $result = $this->emailService->queueWelcome($user);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al encolar email de bienvenida: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar configuración de email
     */
    public function checkConfiguration(Request $request): JsonResponse
    {
        try {
            // Solo administradores pueden verificar configuración
            if (!$request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $result = $this->emailService->checkEmailConfiguration();

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de emails
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            // Solo administradores pueden ver estadísticas
            if (!$request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $result = $this->emailService->getEmailStats();

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de prueba
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'type' => 'required|in:order,welcome,password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Solo administradores pueden enviar emails de prueba
            if (!$request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $email = $request->email;
            $type = $request->type;

            switch ($type) {
                case 'order':
                    // Crear un pedido de prueba
                    $order = Order::with(['user', 'items'])->first();
                    if ($order) {
                        $result = $this->emailService->sendOrderConfirmation($order);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'No hay pedidos disponibles para la prueba'
                        ], 400);
                    }
                    break;

                case 'welcome':
                    // Crear un usuario de prueba
                    $user = new User([
                        'name' => 'Usuario de Prueba',
                        'email' => $email
                    ]);
                    $result = $this->emailService->sendWelcome($user);
                    break;

                case 'password':
                    // Crear un usuario de prueba
                    $user = new User([
                        'name' => 'Usuario de Prueba',
                        'email' => $email
                    ]);
                    $token = \Illuminate\Support\Str::random(60);
                    $result = $this->emailService->sendPasswordReset($user, $token);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipo de email no válido'
                    ], 400);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar email de prueba: ' . $e->getMessage()
            ], 500);
        }
    }
}
