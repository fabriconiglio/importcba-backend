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
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'first_name')
                            ->searchable()
                            ->required()
                            ->label('Cliente'),
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
                    ->label('Nro. Pedido'),
                Tables\Columns\TextColumn::make('user.first_name')
                    ->searchable()
                    ->label('Cliente'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'processing' => 'Procesando',
                        'shipped' => 'Enviado',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('ARS')
                    ->sortable()
                    ->label('Monto Total'),
                Tables\Columns\SelectColumn::make('payment_status')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'failed' => 'Fallido',
                        'refunded' => 'Reembolsado',
                    ])
                    ->label('Estado Pago'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Fecha Creación'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Fecha Act.'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
