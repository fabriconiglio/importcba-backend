<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Mail\OrderConfirmationMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Services\BrevoApiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private BrevoApiService $brevoApi;

    public function __construct()
    {
        $this->brevoApi = new BrevoApiService();
    }
    /**
     * Enviar email de confirmación de pedido
     */
    public function sendOrderConfirmation(Order $order): array
    {
        try {
            // Cargar relaciones necesarias
            $order->load(['user', 'items']);

            if (!$order->user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado para el pedido'
                ];
            }

            Mail::to($order->user->email)
                ->send(new OrderConfirmationMail($order));

            Log::info('Email de confirmación de pedido enviado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_email' => $order->user->email
            ]);

            return [
                'success' => true,
                'message' => 'Email de confirmación enviado correctamente',
                'data' => [
                    'order_id' => $order->id,
                    'user_email' => $order->user->email
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al enviar email de confirmación de pedido: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_email' => $order->user?->email,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar email de confirmación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de recuperación de contraseña
     */
    public function sendPasswordReset(User $user, string $token): array
    {
        try {
            // Usar directamente Brevo API (SMTP está bloqueado en producción)
            $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
            
            $result = $this->brevoApi->sendEmail([
                'to_email' => $user->email,
                'to_name' => $user->name,
                'subject' => 'Recuperar contraseña - ' . config('app.name'),
                'html_content' => view('emails.auth.password-reset', [
                    'user' => $user,
                    'token' => $token,
                    'resetUrl' => $resetUrl,
                    'companyName' => config('app.name'),
                    'frontendUrl' => config('app.frontend_url'),
                    'supportEmail' => config('mail.from.address'),
                    'expiresAt' => now()->addHours(1)->format('d/m/Y H:i'),
                    'companyLogo' => config('app.url') . '/images/logo.png',
                ])->render(),
                'sender_email' => config('mail.from.address'),
                'sender_name' => config('mail.from.name'),
            ]);

            if ($result['success']) {
                Log::info('Email de recuperación de contraseña enviado via Brevo API', [
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]);

                return [
                    'success' => true,
                    'message' => 'Email de recuperación enviado correctamente',
                    'data' => [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'method' => 'brevo_api'
                    ]
                ];
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar email de recuperación via Brevo API: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'api_error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar email de recuperación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de bienvenida
     */
    public function sendWelcome(User $user): array
    {
        try {
            Mail::to($user->email)
                ->send(new WelcomeMail($user));

            Log::info('Email de bienvenida enviado', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Email de bienvenida enviado correctamente',
                'data' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al enviar email de bienvenida: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar email de bienvenida: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de confirmación de pedido en cola
     */
    public function queueOrderConfirmation(Order $order): array
    {
        try {
            // Cargar relaciones necesarias
            $order->load(['user', 'items']);

            if (!$order->user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado para el pedido'
                ];
            }

            Mail::to($order->user->email)
                ->queue(new OrderConfirmationMail($order));

            Log::info('Email de confirmación de pedido encolado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_email' => $order->user->email
            ]);

            return [
                'success' => true,
                'message' => 'Email de confirmación encolado correctamente',
                'data' => [
                    'order_id' => $order->id,
                    'user_email' => $order->user->email,
                    'queued' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al encolar email de confirmación de pedido: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_email' => $order->user?->email,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al encolar email de confirmación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de recuperación de contraseña en cola
     */
    public function queuePasswordReset(User $user, string $token): array
    {
        try {
            Mail::to($user->email)
                ->queue(new PasswordResetMail($user, $token));

            Log::info('Email de recuperación de contraseña encolado', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Email de recuperación encolado correctamente',
                'data' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'queued' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al encolar email de recuperación de contraseña: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al encolar email de recuperación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de bienvenida en cola
     */
    public function queueWelcome(User $user): array
    {
        try {
            Mail::to($user->email)
                ->queue(new WelcomeMail($user));

            Log::info('Email de bienvenida encolado', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Email de bienvenida encolado correctamente',
                'data' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'queued' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al encolar email de bienvenida: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al encolar email de bienvenida: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar configuración de email
     */
    public function checkEmailConfiguration(): array
    {
        try {
            $config = [
                'driver' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'frontend_url' => config('app.frontend_url'),
                'app_name' => config('app.name'),
            ];

            // Verificar configuración básica
            if (empty($config['from_address']) || $config['from_address'] === 'hello@example.com') {
                return [
                    'success' => false,
                    'message' => 'Configuración de email incompleta',
                    'data' => $config
                ];
            }

            return [
                'success' => true,
                'message' => 'Configuración de email correcta',
                'data' => $config
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar configuración: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email simple usando API de Brevo
     */
    public function sendSimpleEmailViaApi(string $toEmail, string $toName, string $subject, string $content): array
    {
        try {
            $result = $this->brevoApi->sendEmail([
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'text_content' => $content,
                'sender_email' => config('mail.from.address'),
                'sender_name' => config('mail.from.name'),
            ]);

            if ($result['success']) {
                Log::info('Email simple enviado via Brevo API', [
                    'to_email' => $toEmail,
                    'subject' => $subject,
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error enviando email simple via Brevo API: ' . $e->getMessage(), [
                'to_email' => $toEmail,
                'subject' => $subject,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar email simple: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Probar conexión con Brevo API
     */
    public function testBrevoApi(): array
    {
        return $this->brevoApi->testConnection();
    }

    /**
     * Obtener estadísticas de emails enviados
     */
    public function getEmailStats(): array
    {
        try {
            // Aquí podrías implementar lógica para obtener estadísticas
            // desde logs, base de datos, o servicios externos
            
            return [
                'success' => true,
                'data' => [
                    'total_sent' => 0,
                    'total_failed' => 0,
                    'last_sent' => null,
                    'queue_size' => 0,
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }
} 