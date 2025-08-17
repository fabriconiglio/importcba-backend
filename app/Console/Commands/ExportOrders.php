<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportOrders extends Command
{
    protected $signature = 'orders:export 
                            {format : Formato de exportación (csv, excel, pdf)}
                            {--status= : Filtrar por estado del pedido}
                            {--date-from= : Fecha desde (YYYY-MM-DD)}
                            {--date-to= : Fecha hasta (YYYY-MM-DD)}
                            {--output= : Archivo de salida}';

    protected $description = 'Exportar pedidos a diferentes formatos';

    public function handle()
    {
        $format = strtolower($this->argument('format'));
        $status = $this->option('status');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $outputFile = $this->option('output');

        if (!in_array($format, ['csv', 'excel', 'pdf'])) {
            $this->error('Formato no válido. Use: csv, excel, o pdf');
            return 1;
        }

        $this->info('Iniciando exportación de pedidos...');

        // Construir query
        $query = Order::with(['user', 'items.product']);

        if ($status) {
            $query->where('status', $status);
            $this->info("Filtrando por estado: {$status}");
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
            $this->info("Filtrando desde: {$dateFrom}");
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
            $this->info("Filtrando hasta: {$dateTo}");
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->warn('No se encontraron pedidos con los filtros especificados.');
            return 0;
        }

        $this->info("Exportando {$orders->count()} pedidos...");

        // Generar nombre de archivo si no se especifica
        if (!$outputFile) {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $outputFile = "orders_export_{$timestamp}.{$format}";
        }

        try {
            switch ($format) {
                case 'csv':
                    $this->exportToCsv($orders, $outputFile);
                    break;
                case 'excel':
                    $this->exportToExcel($orders, $outputFile);
                    break;
                case 'pdf':
                    $this->exportToPdf($orders, $outputFile);
                    break;
            }

            $this->info("Exportación completada exitosamente!");
            $this->info("Archivo guardado en: {$outputFile}");

        } catch (\Exception $e) {
            $this->error("Error durante la exportación: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function exportToCsv($orders, $filename)
    {
        $headers = [
            'Número de Pedido',
            'Cliente',
            'Email',
            'Estado',
            'Estado del Pago',
            'Subtotal',
            'Impuestos',
            'Envío',
            'Descuento',
            'Total',
            'Fecha de Creación',
            'Items',
        ];

        $csvContent = implode(',', $headers) . "\n";

        foreach ($orders as $order) {
            $items = $order->items->map(function ($item) {
                return "{$item->product->name} (x{$item->quantity})";
            })->implode('; ');

            $row = [
                $order->order_number,
                $order->user->first_name ?? 'N/A',
                $order->user->email ?? 'N/A',
                $order->status,
                $order->payment_status,
                $order->subtotal,
                $order->tax_amount,
                $order->shipping_cost,
                $order->discount_amount,
                $order->total_amount,
                $order->created_at->format('d/m/Y H:i'),
                $items,
            ];

            $csvContent .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        File::put($filename, $csvContent);
    }

    protected function exportToExcel($orders, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Título del reporte
        $sheet->setCellValue('A1', 'REPORTE DE PEDIDOS');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Información del reporte
        $sheet->setCellValue('A2', 'Generado el: ' . Carbon::now()->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->setCellValue('A3', 'Total de pedidos: ' . $orders->count());
        $sheet->mergeCells('A3:L3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Encabezados
        $headers = [
            'Número de Pedido',
            'Cliente',
            'Email',
            'Estado',
            'Estado del Pago',
            'Subtotal',
            'Impuestos',
            'Envío',
            'Descuento',
            'Total',
            'Fecha de Creación',
            'Items',
        ];
        
        $row = 5;
        foreach ($headers as $col => $header) {
            $column = chr(65 + $col); // Convertir número a letra (0=A, 1=B, etc.)
            $cell = $sheet->getCell($column . $row);
            $cell->setValue($header);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        }
        
        // Datos
        $row = 6;
        foreach ($orders as $order) {
            $items = $order->items->map(function ($item) {
                return "{$item->product->name} (x{$item->quantity})";
            })->implode('; ');
            
            $sheet->getCell('A' . $row)->setValue($order->order_number);
            $sheet->getCell('B' . $row)->setValue($order->user->first_name ?? 'N/A');
            $sheet->getCell('C' . $row)->setValue($order->user->email ?? 'N/A');
            $sheet->getCell('D' . $row)->setValue($order->status);
            $sheet->getCell('E' . $row)->setValue($order->payment_status);
            $sheet->getCell('F' . $row)->setValue($order->subtotal);
            $sheet->getCell('G' . $row)->setValue($order->tax_amount);
            $sheet->getCell('H' . $row)->setValue($order->shipping_cost);
            $sheet->getCell('I' . $row)->setValue($order->discount_amount);
            $sheet->getCell('J' . $row)->setValue($order->total_amount);
            $sheet->getCell('K' . $row)->setValue($order->created_at->format('d/m/Y H:i'));
            $sheet->getCell('L' . $row)->setValue($items);
            
            $row++;
        }
        
        // Autoajustar columnas
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Bordes para los datos
        $dataRange = 'A5:L' . ($row - 1);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Formato de moneda para columnas numéricas
        $sheet->getStyle('F6:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G6:G' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H6:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('I6:I' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('J6:J' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Guardar archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        
        $this->info("Archivo Excel guardado: {$filename}");
    }

    protected function exportToPdf($orders, $filename)
    {
        // Crear vista HTML para el PDF
        $html = view('exports.orders-pdf', [
            'orders' => $orders,
            'generatedAt' => Carbon::now()->format('d/m/Y H:i:s'),
            'totalOrders' => $orders->count(),
            'totalRevenue' => $orders->sum('total_amount'),
        ])->render();

        // Generar PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Guardar PDF
        $pdf->save($filename);
        
        $this->info("Archivo PDF guardado: {$filename}");
    }
} 