<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pedidos</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #2392D2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2392D2;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        
        .header-info {
            color: #666;
            font-size: 14px;
        }
        
        .summary {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #2392D2;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .order {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .order-header {
            background-color: #2392D2;
            color: white;
            padding: 10px 15px;
            border-radius: 5px 5px 0 0;
        }
        
        .order-number {
            font-size: 16px;
            font-weight: bold;
        }
        
        .order-status {
            float: right;
            background-color: white;
            color: #2392D2;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .order-content {
            padding: 15px;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-group h4 {
            margin: 0 0 8px 0;
            color: #2392D2;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .info-group p {
            margin: 0 0 5px 0;
            font-size: 11px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 11px;
        }
        
        .items-table th:first-child,
        .items-table td:first-child {
            width: 50%;
        }
        
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .order {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE PEDIDOS</h1>
        <div class="header-info">
            Generado el: {{ $generatedAt }}<br>
            Sistema de Gestión Ecommerce
        </div>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ $totalOrders }}</div>
                <div class="summary-label">Total de Pedidos</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">${{ number_format($totalRevenue, 2) }}</div>
                <div class="summary-label">Ingresos Totales</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ $orders->where('status', 'pending')->count() }}</div>
                <div class="summary-label">Pedidos Pendientes</div>
            </div>
        </div>
    </div>

    @foreach($orders as $index => $order)
        @if($index > 0 && $index % 3 == 0)
            <div class="page-break"></div>
        @endif
        
        <div class="order">
            <div class="order-header">
                <span class="order-number">Pedido: {{ $order->order_number }}</span>
                <span class="order-status">{{ ucfirst($order->status) }}</span>
            </div>
            
            <div class="order-content">
                <div class="order-info">
                    <div class="info-group">
                        <h4>Información del Cliente</h4>
                        <p><strong>Nombre:</strong> {{ $order->user->first_name ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $order->user->email ?? 'N/A' }}</p>
                        <p><strong>Teléfono:</strong> {{ $order->user->phone ?? 'N/A' }}</p>
                    </div>
                    
                    <div class="info-group">
                        <h4>Detalles del Pedido</h4>
                        <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        <p><strong>Estado:</strong> {{ ucfirst($order->status) }}</p>
                        <p><strong>Pago:</strong> {{ ucfirst($order->payment_status) }}</p>
                    </div>
                </div>
                
                <div class="info-group">
                    <h4>Direcciones</h4>
                    <p><strong>Envío:</strong> {{ is_array($order->shipping_address) ? implode(', ', $order->shipping_address) : $order->shipping_address }}</p>
                    <p><strong>Facturación:</strong> {{ is_array($order->billing_address) ? implode(', ', $order->billing_address) : $order->billing_address }}</p>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product->name ?? 'Producto no disponible' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->unit_price, 2) }}</td>
                                <td>${{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                            <td>${{ number_format($order->subtotal, 2) }}</td>
                        </tr>
                        @if($order->tax_amount > 0)
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Impuestos:</strong></td>
                                <td>${{ number_format($order->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($order->shipping_cost > 0)
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Envío:</strong></td>
                                <td>${{ number_format($order->shipping_cost, 2) }}</td>
                            </tr>
                        @endif
                        @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Descuento:</strong></td>
                                <td>-${{ number_format($order->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;"><strong>TOTAL:</strong></td>
                            <td><strong>${{ number_format($order->total_amount, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                @if($order->notes)
                    <div style="margin-top: 15px;">
                        <h4 style="color: #2392D2; margin: 0 0 8px 0; font-size: 12px;">NOTAS:</h4>
                        <p style="margin: 0; font-size: 11px; color: #666;">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema de gestión de ecommerce.</p>
        <p>Para más información, contacte al administrador del sistema.</p>
    </div>
</body>
</html> 