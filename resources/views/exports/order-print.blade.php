<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido {{ $order->order_number }} - Import CBA</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c4b8e;
            padding-bottom: 20px;
        }
        
        .company-logo {
            font-size: 28px;
            font-weight: bold;
            color: #2c4b8e;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 20px;
            margin-top: 15px;
            color: #666;
            font-weight: bold;
        }
        
        .print-actions {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #2c4b8e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #1e3766;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c4b8e;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 140px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-processing { background: #e2e3f1; color: #383d41; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 12px 8px;
            text-align: left;
        }
        
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 13px;
        }
        
        .items-table td:last-child,
        .items-table th:last-child {
            text-align: right;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .totals-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        
        .totals-table {
            border-collapse: collapse;
            min-width: 300px;
        }
        
        .totals-table td {
            padding: 8px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .totals-table .total-label {
            font-weight: bold;
            text-align: right;
        }
        
        .totals-table .total-final {
            font-size: 18px;
            font-weight: bold;
            border-top: 3px solid #2c4b8e;
            border-bottom: 3px solid #2c4b8e;
            background: #f8f9fa;
        }
        
        .addresses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        .address-block {
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #2c4b8e;
        }
        
        .address-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            color: #2c4b8e;
        }
        
        .notes-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            text-align: center;
            font-size: 11px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Botones de acción (solo visible en pantalla) -->
        <div class="print-actions no-print">
            <button onclick="window.print()" class="btn">🖨️ Imprimir</button>
            <button onclick="window.close()" class="btn btn-secondary">✖️ Cerrar</button>
        </div>

        <!-- Encabezado -->
        <div class="header">
            <div class="company-logo">Import CBA</div>
            <div class="document-title">Detalle del Pedido</div>
        </div>

        <!-- Información del Pedido -->
        <div class="section">
            <div class="section-title">📋 Información del Pedido</div>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <div class="info-label">Número de Pedido:</div>
                        <div class="info-value"><strong>{{ $order->order_number }}</strong></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fecha:</div>
                        <div class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
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
                    <div class="info-item">
                        <div class="info-label">Estado del Pago:</div>
                        <div class="info-value">
                            @switch($order->payment_status)
                                @case('pending') ⏳ Pendiente @break
                                @case('paid') ✅ Pagado @break
                                @case('failed') ❌ Fallido @break
                                @case('refunded') 🔄 Reembolsado @break
                                @default {{ $order->payment_status }}
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos del Cliente -->
        <div class="section">
            <div class="section-title">👤 Datos del Cliente</div>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <div class="info-label">Nombre:</div>
                        <div class="info-value">
                            @if($order->user->first_name || $order->user->last_name)
                                {{ trim($order->user->first_name . ' ' . $order->user->last_name) }}
                            @else
                                {{ $order->user->name ?? 'Sin nombre' }}
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $order->user->email }}</div>
                    </div>
                </div>
                <div>
                    @if($order->user->phone)
                    <div class="info-item">
                        <div class="info-label">Teléfono:</div>
                        <div class="info-value">{{ $order->user->phone }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Productos -->
        @if($order->items && $order->items->count() > 0)
        <div class="section">
            <div class="section-title">📦 Productos</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="width: 80px;">Cantidad</th>
                        <th style="width: 120px;">Precio Unitario</th>
                        <th style="width: 120px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Producto eliminado' }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">${{ number_format($item->price, 2, ',', '.') }}</td>
                        <td style="text-align: right;"><strong>${{ number_format($item->quantity * $item->price, 2, ',', '.') }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Direcciones -->
        <div class="section">
            <div class="section-title">📍 Direcciones</div>
            <div class="addresses-grid">
                @if($order->shipping_address)
                <div class="address-block">
                    <div class="address-title">🚚 Dirección de Envío</div>
                    @if(is_array($order->shipping_address))
                        @foreach($order->shipping_address as $key => $value)
                            <div>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</div>
                        @endforeach
                    @else
                        {!! nl2br(e($order->shipping_address)) !!}
                    @endif
                </div>
                @endif
                
                @if($order->billing_address)
                <div class="address-block">
                    <div class="address-title">🧾 Dirección de Facturación</div>
                    @if(is_array($order->billing_address))
                        @foreach($order->billing_address as $key => $value)
                            <div>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</div>
                        @endforeach
                    @else
                        {!! nl2br(e($order->billing_address)) !!}
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Totales -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal:</td>
                    <td style="text-align: right;">${{ number_format($order->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if($order->tax_amount > 0)
                <tr>
                    <td class="total-label">Impuestos:</td>
                    <td style="text-align: right;">${{ number_format($order->tax_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
                @if($order->shipping_cost > 0)
                <tr>
                    <td class="total-label">Envío:</td>
                    <td style="text-align: right;">${{ number_format($order->shipping_cost, 2, ',', '.') }}</td>
                </tr>
                @endif
                @if($order->discount_amount > 0)
                <tr>
                    <td class="total-label">Descuento:</td>
                    <td style="text-align: right; color: #dc3545;">-${{ number_format($order->discount_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-final">
                    <td class="total-label">TOTAL:</td>
                    <td style="text-align: right;"><strong>${{ number_format($order->total_amount, 2, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Notas -->
        @if($order->notes)
        <div class="section">
            <div class="section-title">📝 Notas</div>
            <div class="notes-section">
                {!! nl2br(e($order->notes)) !!}
            </div>
        </div>
        @endif

        <!-- Pie de página -->
        <div class="footer">
            <p><strong>Import CBA</strong> - Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
            <p>Este es un documento de respaldo de su pedido. Gracias por su compra.</p>
        </div>
    </div>

    <script>
        // Auto-abrir el diálogo de impresión al cargar la página
        window.addEventListener('load', function() {
            // Pequeño delay para asegurar que el contenido esté completamente cargado
            setTimeout(function() {
                window.print();
            }, 500);
        });
        
        // Cerrar la ventana después de imprimir o cancelar
        window.addEventListener('afterprint', function() {
            window.close();
        });
    </script>
</body>
</html>