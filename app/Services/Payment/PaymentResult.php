<?php

namespace App\Services\Payment;

class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $paymentId = null,
        public ?string $transactionId = null,
        public ?array $data = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null
    ) {}

    /**
     * Crear resultado exitoso
     */
    public static function success(string $message, ?string $paymentId = null, ?string $transactionId = null, ?array $data = null): self
    {
        return new self(true, $message, $paymentId, $transactionId, $data);
    }

    /**
     * Crear resultado fallido
     */
    public static function failure(string $message, ?string $errorCode = null, ?string $errorMessage = null): self
    {
        return new self(false, $message, null, null, null, $errorCode, $errorMessage);
    }

    /**
     * Obtener datos como array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'payment_id' => $this->paymentId,
            'transaction_id' => $this->transactionId,
            'data' => $this->data,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
} 