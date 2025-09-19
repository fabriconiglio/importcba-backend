<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // MOD-004 (main): Enviar email de confirmación cuando se crea un pedido
        Log::info('Pedido creado, enviando email de confirmación', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_email' => $order->user?->email,
            'status' => $order->status
        ]);

        try {
            $result = $this->emailService->sendOrderConfirmation($order);
            
            if ($result['success']) {
                Log::info('Email de confirmación enviado exitosamente', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            } else {
                Log::error('Error al enviar email de confirmación', [
                    'order_id' => $order->id,
                    'error' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar email de confirmación', [
                'order_id' => $order->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Si el estado cambió a 'delivered', reducir el stock
        if ($order->isDirty('status') && $order->status === 'delivered') {
            $this->handleDeliveredOrder($order);
        }
    }

    /**
     * Manejar la reducción de stock cuando un pedido es entregado
     */
    private function handleDeliveredOrder(Order $order): void
    {
        try {
            DB::beginTransaction();

            foreach ($order->items as $item) {
                $product = $item->product;
                
                if (!$product) {
                    Log::warning("Producto no encontrado para el item de pedido", [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'product_id' => $item->product_id
                    ]);
                    continue;
                }

                // Decrementar el stock
                if (!$product->decrementStock($item->quantity)) {
                    Log::error("Error al reducir stock del producto", [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'current_stock' => $product->stock_quantity
                    ]);
                    throw new \Exception("Stock insuficiente para el producto {$product->name}");
                }

                Log::info("Stock reducido correctamente", [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'new_stock' => $product->fresh()->stock_quantity
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al procesar la entrega del pedido: " . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
        }
    }
}