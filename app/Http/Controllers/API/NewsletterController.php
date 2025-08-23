<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    /**
     * Suscribir un email al newsletter
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'source' => 'string|max:100'
            ], [
                'email.required' => 'El email es requerido',
                'email.email' => 'El formato del email no es válido',
                'email.max' => 'El email no puede tener más de 255 caracteres'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $source = $request->source ?? 'website';

            // Verificar si ya está suscrito
            $existingSubscription = NewsletterSubscription::where('email', $email)->first();

            if ($existingSubscription) {
                if ($existingSubscription->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este email ya está suscrito a nuestro newsletter'
                    ], 409);
                } else {
                    // Reactivar suscripción
                    $existingSubscription->resubscribe();
                    
                    return response()->json([
                        'success' => true,
                        'message' => '¡Bienvenido de vuelta! Tu suscripción ha sido reactivada',
                        'data' => [
                            'email' => $existingSubscription->email,
                            'subscribed_at' => $existingSubscription->subscribed_at
                        ]
                    ]);
                }
            }

            // Crear nueva suscripción
            $subscription = NewsletterSubscription::create([
                'email' => $email,
                'subscription_source' => $source
            ]);

            Log::info('Nueva suscripción al newsletter', [
                'email' => $email,
                'source' => $source,
                'id' => $subscription->id
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Gracias por suscribirte! Recibirás nuestras novedades en tu email',
                'data' => [
                    'email' => $subscription->email,
                    'subscribed_at' => $subscription->subscribed_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en suscripción al newsletter', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'no_email'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno. Por favor intenta nuevamente'
            ], 500);
        }
    }

    /**
     * Desuscribir un email del newsletter
     */
    public function unsubscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email inválido'
                ], 422);
            }

            $subscription = NewsletterSubscription::where('email', $request->email)
                ->where('is_active', true)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una suscripción activa para este email'
                ], 404);
            }

            $subscription->unsubscribe();

            return response()->json([
                'success' => true,
                'message' => 'Te has desuscrito exitosamente del newsletter'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en desuscripción del newsletter', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'no_email'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del newsletter (para admin)
     */
    public function stats()
    {
        try {
            $totalSubscriptions = NewsletterSubscription::count();
            $activeSubscriptions = NewsletterSubscription::active()->count();
            $inactiveSubscriptions = $totalSubscriptions - $activeSubscriptions;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_subscriptions' => $totalSubscriptions,
                    'active_subscriptions' => $activeSubscriptions,
                    'inactive_subscriptions' => $inactiveSubscriptions,
                    'sources' => NewsletterSubscription::active()
                        ->selectRaw('subscription_source, COUNT(*) as count')
                        ->groupBy('subscription_source')
                        ->get()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
}
