<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Autenticación Social",
 *     description="Endpoints para autenticación con Google y Facebook"
 * )
 */
class SocialAuthController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/auth/{provider}/redirect",
     *     summary="Redirigir a proveedor OAuth",
     *     description="Redirige al usuario al proveedor OAuth especificado",
     *     tags={"Autenticación Social"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Proveedor OAuth (google o facebook)",
     *         @OA\Schema(type="string", enum={"google", "facebook"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Redirección exitosa"
     *     )
     * )
     */
    public function redirectToProvider(string $provider): JsonResponse
    {
        try {
            if (!in_array($provider, ['google', 'facebook'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no soportado'
                ], 400);
            }

            $redirectUrl = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'success' => true,
                'data' => [
                    'redirect_url' => $redirectUrl
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error en redirección OAuth: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al redirigir al proveedor OAuth'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/{provider}/callback",
     *     summary="Callback de proveedor OAuth",
     *     description="Maneja el callback del proveedor OAuth y autentica al usuario",
     *     tags={"Autenticación Social"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Proveedor OAuth (google o facebook)",
     *         @OA\Schema(type="string", enum={"google", "facebook"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="¡Autenticación exitosa!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function handleProviderCallback(string $provider, Request $request): JsonResponse|RedirectResponse
    {
        try {
            if (!in_array($provider, ['google', 'facebook'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no soportado'
                ], 400);
            }

            // Obtener datos del usuario del proveedor OAuth
            try {
                $socialUser = Socialite::driver($provider)->stateless()->user();
            } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
                Log::error("Estado OAuth inválido: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Estado de autenticación inválido. Por favor, intenta de nuevo.'
                ], 400);
            } catch (\Exception $e) {
                Log::error("Error obteniendo usuario OAuth: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener datos del usuario de ' . $provider
                ], 400);
            }

            if (!$socialUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información del usuario'
                ], 400);
            }

            // Buscar o crear usuario
            $user = $this->findOrCreateUser($provider, $socialUser);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear o encontrar usuario'
                ], 500);
            }

            // Generar token
            $token = $user->createToken('social_auth_token')->plainTextToken;

            // Redirigir al frontend con el token
            $frontendUrl = config('app.frontend_url');
            $redirectUrl = $frontendUrl . '/login?token=' . $token . '&user=' . base64_encode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]));

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            Log::error("Error en callback OAuth: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'provider' => $provider
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error en la autenticación social: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Encontrar o crear usuario basado en datos sociales
     */
    private function findOrCreateUser(string $provider, $socialUser): ?User
    {
        try {
            // Buscar usuario por email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Usuario existe, actualizar información del proveedor
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'email_verified_at' => now(), // Email verificado por OAuth
                ]);

                return $user;
            }

            // Crear nuevo usuario
            $user = User::create([
                'name' => $this->extractName($socialUser, $provider),
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(16)), // Contraseña aleatoria
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => now(), // Email verificado por OAuth
            ]);

            // Asignar rol de cliente
            $user->assignRole('customer');

            return $user;

        } catch (\Exception $e) {
            Log::error("Error creando/buscando usuario OAuth: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraer nombre del usuario según el proveedor
     */
    private function extractName($socialUser, string $provider): string
    {
        $name = $socialUser->getName();

        if (empty($name)) {
            // Fallback para diferentes proveedores
            switch ($provider) {
                case 'google':
                    $name = $socialUser->getNickname() ?: 'Usuario Google';
                    break;
                case 'facebook':
                    $name = $socialUser->getNickname() ?: 'Usuario Facebook';
                    break;
                default:
                    $name = 'Usuario OAuth';
            }
        }

        return $name;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/{provider}/disconnect",
     *     summary="Desconectar cuenta social",
     *     description="Desconecta la cuenta del usuario del proveedor OAuth",
     *     tags={"Autenticación Social"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Proveedor OAuth a desconectar",
     *         @OA\Schema(type="string", enum={"google", "facebook"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Desconexión exitosa"
     *     )
     * )
     */
    public function disconnectProvider(string $provider, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que el usuario tenga este proveedor conectado
            if ($user->provider !== $provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este proveedor no está conectado a tu cuenta'
                ], 400);
            }

            // Desconectar proveedor
            $user->update([
                'provider' => null,
                'provider_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor desconectado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error("Error desconectando proveedor OAuth: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al desconectar el proveedor'
            ], 500);
        }
    }
} 