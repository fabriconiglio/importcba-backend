<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAdminNotificationMail extends Mailable
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
            subject: "🛒 Nuevo Pedido Recibido - #{$this->order->order_number}",
            tags: ['order', 'admin-notification'],
            metadata: [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'user_id' => $this->order->user_id,
                'total_amount' => $this->order->total_amount,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.admin-notification',
            with: [
                'order' => $this->order,
                'user' => $this->order->user,
                'items' => $this->order->items,
                'shippingAddress' => $this->order->shipping_address,
                'billingAddress' => $this->order->billing_address,
                'adminPanelUrl' => config('app.url') . "/admin/orders/{$this->order->id}",
                'companyName' => config('app.name'),
                'totalItems' => $this->order->items->sum('quantity'),
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
