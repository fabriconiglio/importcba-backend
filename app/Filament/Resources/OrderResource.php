<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Section::make()->schema([
                        Forms\Components\TextInput::make('user.first_name')
                            ->label('Cliente')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                if ($record && $record->user) {
                                    return $record->user->first_name . ' ' . ($record->user->last_name ?? '') . ' (' . $record->user->email . ')';
                                }
                                return 'Cliente no encontrado';
                            }),
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->maxLength(50)
                            ->label('Número de Pedido'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'processing' => 'Procesando',
                                'shipped' => 'Enviado',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->required()
                            ->label('Estado del Pedido'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pendiente',
                                'paid' => 'Pagado',
                                'failed' => 'Fallido',
                                'refunded' => 'Reembolsado',
                            ])
                            ->required()
                            ->label('Estado del Pago'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options(PaymentMethod::where('is_active', true)->pluck('name', 'name'))
                            ->searchable(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->label('Notas'),
                    ])->columnSpan(2),

                    Section::make('Montos')->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->label('Subtotal')
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('$')
                            ->label('Impuestos')
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                        Forms\Components\TextInput::make('shipping_cost')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('$')
                            ->label('Costo de Envío')
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('$')
                            ->label('Descuento')
                            ->reactive()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->readOnly()
                            ->label('Monto Total'),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->label('Nro. Pedido')
                    ->copyable()
                    ->copyMessage('Número de pedido copiado'),
                Tables\Columns\TextColumn::make('cliente')
                    ->label('Cliente')
                    ->getStateUsing(function ($record): string {
                        $user = $record->user;
                        if (!$user) {
                            return 'Sin cliente';
                        }
                        $fullName = trim($user->first_name . ' ' . ($user->last_name ?? ''));
                        return $fullName ?: $user->email;
                    })
                    ->searchable(false)
                    ->description(fn ($record) => $record->user?->email ?? 'Sin email'),
                Tables\Columns\TextColumn::make('status')
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
                    })
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('ARS')
                    ->sortable()
                    ->label('Monto Total')
                    ->color(fn ($record) => $record->total_amount > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('payment_status')
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
                    })
                    ->label('Estado Pago'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Fecha Creación'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Fecha Act.'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado del Pedido')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'processing' => 'Procesando',
                        'shipped' => 'Enviado',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado del Pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'failed' => 'Fallido',
                        'refunded' => 'Reembolsado',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('Rango de Fechas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles'),
                Tables\Actions\Action::make('change_status')
                    ->label('Cambiar Estado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nuevo Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'processing' => 'Procesando',
                                'shipped' => 'Enviado',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('status_notes')
                            ->label('Notas del Cambio')
                            ->placeholder('Motivo del cambio de estado...'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                            'notes' => $record->notes . "\n\n" . now()->format('d/m/Y H:i') . " - Estado cambiado a: " . $data['status'] . "\nNotas: " . ($data['status_notes'] ?? 'Sin notas'),
                        ]);
                    })
                    ->successNotificationTitle('Estado del pedido actualizado'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('change_status_bulk')
                        ->label('Cambiar Estado Masivo')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuevo Estado')
                                ->options([
                                    'pending' => 'Pendiente',
                                    'confirmed' => 'Confirmado',
                                    'processing' => 'Procesando',
                                    'shipped' => 'Enviado',
                                    'delivered' => 'Entregado',
                                    'cancelled' => 'Cancelado',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'status' => $data['status'],
                                    'notes' => $record->notes . "\n\n" . now()->format('d/m/Y H:i') . " - Estado cambiado masivamente a: " . $data['status'],
                                ]);
                            });
                        })
                        ->successNotificationTitle('Estados de pedidos actualizados masivamente'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function updateTotal(Get $get, Set $set): void
    {
        $subtotal = floatval($get('subtotal')) ?: 0;
        $tax = floatval($get('tax_amount')) ?: 0;
        $shipping = floatval($get('shipping_cost')) ?: 0;
        $discount = floatval($get('discount_amount')) ?: 0;

        $total = $subtotal + $tax + $shipping - $discount;

        $set('total_amount', number_format($total, 2, '.', ''));
    }
}
