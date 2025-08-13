<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmación de Pedido #{$this->order->order_number}",
            tags: ['order', 'confirmation'],
            metadata: [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'user_id' => $this->order->user_id,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.confirmation',
            with: [
                'order' => $this->order,
                'user' => $this->order->user,
                'items' => $this->order->items,
                'shippingAddress' => $this->order->shipping_address,
                'billingAddress' => $this->order->billing_address,
                'orderUrl' => config('app.frontend_url') . "/orders/{$this->order->id}",
                'supportEmail' => config('mail.from.address'),
                'companyName' => config('app.name'),
                'companyLogo' => config('app.url') . '/images/logo.png',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
