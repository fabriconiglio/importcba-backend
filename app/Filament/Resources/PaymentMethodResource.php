<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Filament\Resources\PaymentMethodResource\RelationManagers;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $modelLabel = 'Método de Pago';
    protected static ?string $pluralModelLabel = 'Métodos de Pago';
    protected static ?string $navigationGroup = 'Tienda';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Método de Pago')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Método')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej: Tarjeta Visa, MercadoPago, Transferencia')
                            ->helperText('Nombre descriptivo del método de pago')
                            ->suffixIcon('heroicon-o-credit-card'),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Método')
                            ->options([
                                'credit_card' => 'Tarjeta de Crédito',
                                'debit_card' => 'Tarjeta de Débito',
                                'bank_transfer' => 'Transferencia Bancaria',
                                'cash_on_delivery' => 'Pago contra entrega',
                                'mercadopago' => 'MercadoPago',
                                'paypal' => 'PayPal',
                                'crypto' => 'Criptomonedas',
                                'gift_card' => 'Tarjeta de Regalo',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Configurar campos específicos según el tipo
                                if (in_array($state, ['credit_card', 'debit_card'])) {
                                    $set('configuration', [
                                        'processor' => 'stripe',
                                        'installments' => '1',
                                        'currency' => 'ARS'
                                    ]);
                                } elseif ($state === 'mercadopago') {
                                    $set('configuration', [
                                        'public_key' => '',
                                        'access_token' => '',
                                        'installments' => '12'
                                    ]);
                                }
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración y Parámetros')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required()
                            ->helperText('Desactivar para pausar temporalmente este método'),
                        Forms\Components\Toggle::make('requires_authentication')
                            ->label('Requiere Autenticación')
                            ->helperText('Marcar si el usuario debe estar logueado para usar este método'),
                        Forms\Components\TextInput::make('processing_fee')
                            ->label('Comisión de Procesamiento (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Comisión adicional por usar este método de pago'),
                        Forms\Components\TextInput::make('priority')
                            ->label('Prioridad')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Orden de prioridad para mostrar en el checkout (1 = más alto)'),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración Técnica')
                    ->schema([
                        Forms\Components\KeyValue::make('configuration')
                            ->label('Configuración Técnica')
                            ->keyLabel('Clave')
                            ->valueLabel('Valor')
                            ->helperText('Configuración específica del método (API Keys, URLs, etc.)')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('instructions')
                            ->label('Instrucciones para el Cliente')
                            ->rows(3)
                            ->placeholder('Ej: Complete los datos de su tarjeta o siga los pasos para MercadoPago')
                            ->helperText('Instrucciones que verá el cliente al seleccionar este método')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_card' => 'Tarjeta de Crédito',
                        'debit_card' => 'Tarjeta de Débito',
                        'bank_transfer' => 'Transferencia Bancaria',
                        'cash_on_delivery' => 'Pago contra entrega',
                        'mercadopago' => 'MercadoPago',
                        'paypal' => 'PayPal',
                        'crypto' => 'Criptomonedas',
                        'gift_card' => 'Tarjeta de Regalo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'credit_card' => 'primary',
                        'debit_card' => 'success',
                        'bank_transfer' => 'info',
                        'cash_on_delivery' => 'warning',
                        'mercadopago' => 'info',
                        'paypal' => 'primary',
                        'crypto' => 'warning',
                        'gift_card' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('processing_fee')
                    ->label('Comisión')
                    ->formatStateUsing(fn($state) => $state ? "{$state}%" : '0%')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'warning' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Método')
                    ->options([
                        'credit_card' => 'Tarjeta de Crédito',
                        'debit_card' => 'Tarjeta de Débito',
                        'bank_transfer' => 'Transferencia Bancaria',
                        'cash_on_delivery' => 'Pago contra entrega',
                        'mercadopago' => 'MercadoPago',
                        'paypal' => 'PayPal',
                        'crypto' => 'Criptomonedas',
                        'gift_card' => 'Tarjeta de Regalo',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar Seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                ]),
            ])
            ->defaultSort('priority', 'asc')
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
