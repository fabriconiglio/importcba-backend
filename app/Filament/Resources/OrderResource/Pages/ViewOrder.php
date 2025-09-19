<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Volver')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
            Actions\Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('orders.print', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('export_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('orders.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Pedido')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Número de Pedido')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'processing' => 'Procesando',
                                'shipped' => 'Enviado',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'processing' => 'primary',
                                'shipped' => 'success',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Estado del Pago')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pendiente',
                                'paid' => 'Pagado',
                                'failed' => 'Fallido',
                                'refunded' => 'Reembolsado',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'paid' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Cliente')
                    ->schema([
                         Infolists\Components\TextEntry::make('cliente_nombre')
                            ->label('Nombre')
                            ->getStateUsing(function ($record): string {
                                // Primero intentar obtener el nombre desde shipping_address
                                $shippingAddress = $record->shipping_address;
                                if ($shippingAddress && isset($shippingAddress['first_name']) && isset($shippingAddress['last_name'])) {
                                    return trim($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']);
                                }
                                
                                // Fallback al usuario si no hay información en shipping_address
                                $user = $record->user;
                                if ($user) {
                                    return trim($user->first_name . ' ' . ($user->last_name ?? ''));
                                }
                                
                                return 'Sin nombre';
                            }),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('cliente_telefono')
                            ->label('Teléfono')
                            ->getStateUsing(function ($record): string {
                                // Obtener teléfono desde shipping_address
                                $shippingAddress = $record->shipping_address;
                                if ($shippingAddress && isset($shippingAddress['phone'])) {
                                    return $shippingAddress['phone'];
                                }
                                
                                // Fallback al usuario si no hay información en shipping_address
                                return $record->user?->phone ?? 'Sin teléfono';
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Montos')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('ARS'),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('Impuestos')
                            ->money('ARS'),
                        Infolists\Components\TextEntry::make('shipping_cost')
                            ->label('Envío')
                            ->money('ARS'),
                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label('Descuento')
                            ->money('ARS'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total')
                            ->money('ARS')
                            ->weight('bold'),
                    ])
                    ->columns(5),

                Infolists\Components\Section::make('Direcciones')
                    ->schema([
                        Infolists\Components\TextEntry::make('shipping_address')
                            ->label('Dirección de Envío')
                            ->listWithLineBreaks(),
                        Infolists\Components\TextEntry::make('billing_address')
                            ->label('Dirección de Facturación')
                            ->listWithLineBreaks(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Notas')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notas del Pedido')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }




}
