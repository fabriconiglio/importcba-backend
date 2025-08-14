<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Emails",
 *     description="Endpoints para gestión de emails y notificaciones"
 * )
 */
class EmailController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/emails/order/{orderId}/confirmation",
     *     summary="Enviar email de confirmación de pedido",
     *     description="Envía un email de confirmación para un pedido específico",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email enviado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de confirmación enviado correctamente"),
     *             @OA\Property(property="email_id", type="string", example="msg_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al enviar email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al enviar email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No tienes permisos para enviar este email",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/emails/order/{orderId}/confirmation/queue",
     *     summary="Enviar email de confirmación en cola",
     *     description="Encola un email de confirmación para un pedido específico (envío asíncrono)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID del pedido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email encolado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de confirmación encolado correctamente"),
     *             @OA\Property(property="job_id", type="string", example="job_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al encolar email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al encolar email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No tienes permisos para enviar este email",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/emails/password-reset",
     *     summary="Enviar email de recuperación de contraseña",
     *     description="Envía un email de recuperación de contraseña al usuario especificado",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@example.com", description="Email del usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email enviado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de recuperación enviado correctamente"),
     *             @OA\Property(property="email_id", type="string", example="msg_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al enviar email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al enviar email de recuperación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/emails/password-reset/queue",
     *     summary="Enviar email de recuperación en cola",
     *     description="Encola un email de recuperación de contraseña (envío asíncrono)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@example.com", description="Email del usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email encolado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de recuperación encolado correctamente"),
     *             @OA\Property(property="job_id", type="string", example="job_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al encolar email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al encolar email de recuperación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/emails/welcome/{userId}",
     *     summary="Enviar email de bienvenida",
     *     description="Envía un email de bienvenida a un usuario específico (solo administradores)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email enviado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de bienvenida enviado correctamente"),
     *             @OA\Property(property="email_id", type="string", example="msg_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al enviar email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al enviar email de bienvenida")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/emails/welcome/{userId}/queue",
     *     summary="Enviar email de bienvenida en cola",
     *     description="Encola un email de bienvenida (envío asíncrono, solo administradores)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email encolado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de bienvenida encolado correctamente"),
     *             @OA\Property(property="job_id", type="string", example="job_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al encolar email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al encolar email de bienvenida")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/emails/check-configuration",
     *     summary="Verificar configuración de email",
     *     description="Verifica la configuración del servicio de email (solo administradores)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Configuración verificada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuración de email verificada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="driver", type="string", example="smtp"),
     *                 @OA\Property(property="host", type="string", example="smtp.gmail.com"),
     *                 @OA\Property(property="port", type="integer", example=587),
     *                 @OA\Property(property="encryption", type="string", example="tls"),
     *                 @OA\Property(property="from_address", type="string", example="noreply@ecommerce.com"),
     *                 @OA\Property(property="from_name", type="string", example="Ecommerce Store")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la configuración",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error en la configuración de email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/emails/stats",
     *     summary="Obtener estadísticas de emails",
     *     description="Obtiene estadísticas del servicio de email (solo administradores)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas de email obtenidas correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_sent", type="integer", example=1250),
     *                 @OA\Property(property="total_failed", type="integer", example=15),
     *                 @OA\Property(property="success_rate", type="number", format="float", example=98.8),
     *                 @OA\Property(property="emails_today", type="integer", example=45),
     *                 @OA\Property(property="emails_this_week", type="integer", example=320),
     *                 @OA\Property(property="emails_this_month", type="integer", example=1250),
     *                 @OA\Property(
     *                     property="by_type",
     *                     type="object",
     *                     @OA\Property(property="order_confirmation", type="integer", example=800),
     *                     @OA\Property(property="password_reset", type="integer", example=300),
     *                     @OA\Property(property="welcome", type="integer", example=150)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al obtener estadísticas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al obtener estadísticas de email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/emails/test",
     *     summary="Enviar email de prueba",
     *     description="Envía un email de prueba para verificar la configuración (solo administradores)",
     *     tags={"Emails"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","type"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com", description="Email de destino"),
     *             @OA\Property(property="type", type="string", enum={"order","welcome","password"}, example="order", description="Tipo de email de prueba")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de prueba enviado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de prueba enviado correctamente"),
     *             @OA\Property(property="email_id", type="string", example="msg_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al enviar email de prueba",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No hay pedidos disponibles para la prueba")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
