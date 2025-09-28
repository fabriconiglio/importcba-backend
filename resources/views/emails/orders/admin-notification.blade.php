<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Pedido - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .order-info {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        .order-info h2 {
            color: #059669;
            margin-top: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background-color: white;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-item strong {
            color: #374151;
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .customer-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #93c5fd;
        }
        .customer-info h3 {
            color: #1d4ed8;
            margin-top: 0;
            font-weight: 600;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .total-section {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .total-section h3 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .address-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #f59e0b;
            border: 1px solid #fbbf24;
        }
        .address-section h4 {
            color: #92400e;
            margin-top: 0;
            font-weight: 600;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 0 10px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .urgent {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 {{ $companyName }}</h1>
            <p><strong>Nuevo Pedido Recibido</strong></p>
        </div>

        <div class="urgent">
            ⚡ ACCIÓN REQUERIDA: Nuevo pedido pendiente de procesamiento
        </div>

        <div class="order-info">
            <h2>📋 Información del Pedido</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Número de Pedido:</strong>
                    {{ $order->order_number }}
                </div>
                <div class="info-item">
                    <strong>Estado:</strong>
                    {{ ucfirst($order->status) }}
                </div>
                <div class="info-item">
                    <strong>Fecha:</strong>
                    {{ $order->created_at->format('d/m/Y H:i') }}
                </div>
                <div class="info-item">
                    <strong>Total de Productos:</strong>
                    {{ $totalItems }} unidades
                </div>
            </div>
        </div>

        <div class="customer-info">
            <h3>👤 Información del Cliente</h3>
            <p><strong>Nombre:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Teléfono:</strong> {{ $shippingAddress['phone'] ?? 'No especificado' }}</p>
        </div>

        <div class="address-section">
            <h4>📍 Dirección de Envío</h4>
            <p>
                {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br>
                {{ $shippingAddress['street_address'] ?? '' }}<br>
                {{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['state'] ?? '' }} {{ $shippingAddress['postal_code'] ?? '' }}<br>
                {{ $shippingAddress['country'] ?? '' }}
            </p>
        </div>

        <h3>🛍️ Productos Pedidos</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product_sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <h3>💰 Total del Pedido: ${{ number_format($order->total_amount, 2) }}</h3>
            <p>
                Subtotal: ${{ number_format($order->subtotal, 2) }} | 
                Envío: ${{ number_format($order->shipping_cost, 2) }} | 
                Descuento: ${{ number_format($order->discount_amount, 2) }}
            </p>
        </div>

        @if($order->notes)
        <div class="address-section">
            <h4>📝 Notas del Cliente</h4>
            <p>{{ $order->notes }}</p>
        </div>
        @endif

        <div class="action-buttons">
            <a href="{{ $adminPanelUrl }}" class="btn">Ver Pedido Completo</a>
            <a href="{{ config('app.url') }}/admin/orders" class="btn btn-success">Gestionar Pedidos</a>
        </div>

        <div class="footer">
            <p>Este email fue enviado automáticamente cuando se creó el pedido {{ $order->order_number }}.</p>
            <p>Para gestionar este pedido, accede al panel de administración.</p>
            <p><strong>{{ $companyName }}</strong> - Sistema de Gestión de Pedidos</p>
        </div>
    </div>
</body>
</html>
