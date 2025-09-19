<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class BrevoApiService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl = 'https://api.brevo.com/v3/';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
        ]);
        $this->apiKey = env('BREVO_API_KEY');
    }

    /**
     * Enviar email usando la API de Brevo
     */
    public function sendEmail(array $data): array
    {
        try {
            if (!$this->apiKey) {
                throw new \Exception('BREVO_API_KEY no está configurada');
            }

            $emailData = [
                'sender' => [
                    'name' => $data['sender_name'] ?? config('mail.from.name'),
                    'email' => $data['sender_email'] ?? config('mail.from.address'),
                ],
                'to' => [
                    [
                        'email' => $data['to_email'],
                        'name' => $data['to_name'] ?? null,
                    ]
                ],
                'subject' => $data['subject'],
                'htmlContent' => $data['html_content'] ?? null,
                'textContent' => $data['text_content'] ?? null,
            ];

            // Si hay contenido HTML, usar solo HTML
            if (!isset($emailData['htmlContent']) && isset($emailData['textContent'])) {
                $emailData['htmlContent'] = nl2br(htmlspecialchars($emailData['textContent']));
            }

            Log::info('Enviando email via Brevo API', [
                'to' => $data['to_email'],
                'subject' => $data['subject'],
            ]);

            $response = $this->client->post('smtp/email', [
                'headers' => [
                    'api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $emailData,
            ]);

            $responseData = json_decode($response->getBody(), true);

            Log::info('Email enviado exitosamente via Brevo API', [
                'message_id' => $responseData['messageId'] ?? null,
                'to' => $data['to_email'],
            ]);

            return [
                'success' => true,
                'message' => 'Email enviado correctamente',
                'data' => $responseData,
            ];

        } catch (RequestException $e) {
            $errorMessage = 'Error de petición HTTP: ' . $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $responseBody;
            }

            Log::error('Error enviando email via Brevo API', [
                'error' => $errorMessage,
                'to' => $data['to_email'] ?? 'unknown',
                'subject' => $data['subject'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (\Exception $e) {
            $errorMessage = 'Error general: ' . $e->getMessage();

            Log::error('Error general enviando email via Brevo API', [
                'error' => $errorMessage,
                'to' => $data['to_email'] ?? 'unknown',
                'subject' => $data['subject'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        }
    }

    /**
     * Obtener información de la cuenta
     */
    public function getAccount(): array
    {
        try {
            $response = $this->client->get('account', [
                'headers' => [
                    'api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo información de cuenta: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar que la API key funciona
     */
    public function testConnection(): array
    {
        try {
            $response = $this->getAccount();
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con Brevo API',
                    'data' => [
                        'email' => $response['data']['email'] ?? 'N/A',
                        'company_name' => $response['data']['companyName'] ?? 'N/A',
                        'plan' => $response['data']['plan'][0]['type'] ?? 'N/A',
                    ],
                ];
            }

            return $response;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error probando conexión: ' . $e->getMessage(),
            ];
        }
    }
}
