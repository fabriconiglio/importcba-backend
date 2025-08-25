<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido {{ $order->order_number }}</title>
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
            margin-bottom: 30px;
            border-bottom: 2px solid #2c4b8e;
            padding-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c4b8e;
            margin-bottom: 5px;
        }
        
        .order-title {
            font-size: 18px;
            margin-top: 10px;
            color: #666;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c4b8e;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            vertical-align: top;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .items-table td:last-child,
        .items-table th:last-child {
            text-align: right;
        }
        
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        
        .totals-table {
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 5px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .totals-table .total-label {
            font-weight: bold;
            text-align: right;
        }
        
        .totals-table .total-final {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #2c4b8e;
            border-bottom: 2px solid #2c4b8e;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-confirmed { background-color: #dbeafe; color: #1e40af; }
        .status-processing { background-color: #e0e7ff; color: #5b21b6; }
        .status-shipped { background-color: #d1fae5; color: #065f46; }
        .status-delivered { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Import CBA</div>
        <div class="order-title">Detalle del Pedido</div>
    </div>

    <div class="section">
        <div class="section-title">Información del Pedido</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Número de Pedido:</div>
                <div class="info-value">{{ $order->order_number }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha:</div>
                <div class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $order->status }}">
                        @switch($order->status)
                            @case('pending') Pendiente @break
                            @case('confirmed') Confirmado @break
                            @case('processing') Procesando @break
                            @case('shipped') Enviado @break
                            @case('delivered') Entregado @break
                            @case('cancelled') Cancelado @break
                            @default {{ $order->status }}
                        @endswitch
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado del Pago:</div>
                <div class="info-value">
                    @switch($order->payment_status)
                        @case('pending') Pendiente @break
                        @case('paid') Pagado @break
                        @case('failed') Fallido @break
                        @case('refunded') Reembolsado @break
                        @default {{ $order->payment_status }}
                    @endswitch
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Cliente</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">
                    @if($order->user->first_name || $order->user->last_name)
                        {{ trim($order->user->first_name . ' ' . $order->user->last_name) }}
                    @else
                        {{ $order->user->name ?? 'Sin nombre' }}
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $order->user->email }}</div>
            </div>
            @if($order->user->phone)
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $order->user->phone }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($order->items && $order->items->count() > 0)
    <div class="section">
        <div class="section-title">Productos</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Producto eliminado' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->price, 2, ',', '.') }}</td>
                    <td>${{ number_format($item->quantity * $item->price, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Direcciones</div>
        <div style="display: table; width: 100%;">
            @if($order->shipping_address)
            <div style="display: table-cell; width: 50%; padding-right: 20px; vertical-align: top;">
                <strong>Dirección de Envío:</strong><br>
                @if(is_array($order->shipping_address))
                    @foreach($order->shipping_address as $line)
                        {{ $line }}<br>
                    @endforeach
                @else
                    {!! nl2br(e($order->shipping_address)) !!}
                @endif
            </div>
            @endif
            
            @if($order->billing_address)
            <div style="display: table-cell; width: 50%; vertical-align: top;">
                <strong>Dirección de Facturación:</strong><br>
                @if(is_array($order->billing_address))
                    @foreach($order->billing_address as $line)
                        {{ $line }}<br>
                    @endforeach
                @else
                    {!! nl2br(e($order->billing_address)) !!}
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td>${{ number_format($order->subtotal, 2, ',', '.') }}</td>
            </tr>
            @if($order->tax_amount > 0)
            <tr>
                <td class="total-label">Impuestos:</td>
                <td>${{ number_format($order->tax_amount, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->shipping_cost > 0)
            <tr>
                <td class="total-label">Envío:</td>
                <td>${{ number_format($order->shipping_cost, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->discount_amount > 0)
            <tr>
                <td class="total-label">Descuento:</td>
                <td>-${{ number_format($order->discount_amount, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-final">
                <td class="total-label">TOTAL:</td>
                <td>${{ number_format($order->total_amount, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if($order->notes)
    <div class="section">
        <div class="section-title">Notas</div>
        <p>{!! nl2br(e($order->notes)) !!}</p>
    </div>
    @endif

    <div class="footer">
        <p>Import CBA - Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>