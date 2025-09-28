<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: white;
        }
        .content {
            padding: 30px;
        }
        .order-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .order-date {
            color: #666;
            font-size: 14px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .items-table th {
            background-color: #2563eb;
            color: white;
            font-weight: 600;
        }
        .product-name {
            font-weight: 500;
        }
        .product-sku {
            color: #666;
            font-size: 12px;
        }
        .totals {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
        }
        .total-row.final {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 15px;
        }
        .addresses {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .address {
            flex: 1;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .address h3 {
            margin: 0 0 10px 0;
            color: #2563eb;
            font-size: 16px;
        }
        .address p {
            margin: 5px 0;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white !important;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
        }
        .cta-button:link,
        .cta-button:visited,
        .cta-button:hover,
        .cta-button:active {
            color: white !important;
            text-decoration: none !important;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #2563eb;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .addresses {
                flex-direction: column;
            }
            .items-table {
                font-size: 12px;
            }
            .items-table th,
            .items-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>¡Gracias por tu compra!</p>
        </div>

        <div class="content">
            <div class="order-info">
                <div class="order-number">Pedido #{{ $order->order_number }}</div>
                <div class="order-date">Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</div>
            </div>

            <h2>Hola {{ $user->name }},</h2>
            <p>Hemos recibido tu pedido y estamos procesándolo. Te enviaremos una actualización cuando tu pedido esté listo para envío.</p>

            <h3>Detalles del Pedido</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <div class="product-name">{{ $item->product_name }}</div>
                            <div class="product-sku">SKU: {{ $item->product_sku }}</div>
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->shipping_cost > 0)
                <div class="total-row">
                    <span>Envío:</span>
                    <span>${{ number_format($order->shipping_cost, 2) }}</span>
                </div>
                @endif
                @if($order->tax_amount > 0)
                <div class="total-row">
                    <span>Impuestos:</span>
                    <span>${{ number_format($order->tax_amount, 2) }}</span>
                </div>
                @endif
                @if($order->discount_amount > 0)
                <div class="total-row">
                    <span>Descuento:</span>
                    <span>-${{ number_format($order->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="total-row final">
                    <span>Total:</span>
                    <span>${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <div class="addresses">
                <div class="address">
                    <h3>Dirección de Envío</h3>
                    <p>{{ $shippingAddress['street_address'] }}</p>
                    <p>{{ $shippingAddress['city'] }}, {{ $shippingAddress['state'] }} {{ $shippingAddress['postal_code'] }}</p>
                    <p>{{ $shippingAddress['country'] }}</p>
                </div>
                @if($billingAddress && $billingAddress !== $shippingAddress)
                <div class="address">
                    <h3>Dirección de Facturación</h3>
                    <p>{{ $billingAddress['street_address'] }}</p>
                    <p>{{ $billingAddress['city'] }}, {{ $billingAddress['state'] }} {{ $billingAddress['postal_code'] }}</p>
                    <p>{{ $billingAddress['country'] }}</p>
                </div>
                @endif
            </div>

            @if($order->notes)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
                <h4>Notas del Pedido:</h4>
                <p>{{ $order->notes }}</p>
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ $orderUrl }}" class="cta-button">Ver Detalles del Pedido</a>
            </div>

            <p style="margin-top: 30px;">
                Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos respondiendo a este email o escribiendo a <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
            </p>

            <p>¡Gracias por elegir {{ $companyName }}!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Todos los derechos reservados.</p>
            <p>Este email fue enviado a {{ $user->email }}</p>
            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </div>
</body>
</html> 