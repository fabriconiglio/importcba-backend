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
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Método')
                    ->required()
                    ->maxLength(100)
                    ->columnSpan('full'),
                Forms\Components\Select::make('type')
                    ->label('Tipo de Método')
                    ->options([
                        'credit_card' => 'Tarjeta de Crédito',
                        'debit_card' => 'Tarjeta de Débito',
                        'bank_transfer' => 'Transferencia Bancaria',
                        'cash_on_delivery' => 'Pago contra entrega',
                        'mercadopago' => 'MercadoPago',
                        'paypal' => 'PayPal',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
                Forms\Components\KeyValue::make('configuration')
                    ->label('Configuración (ej: API Key, Secret)')
                    ->keyLabel('Clave')
                    ->valueLabel('Valor')
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
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
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'credit_card' => 'primary',
                        'debit_card' => 'success',
                        'bank_transfer' => 'info',
                        'cash_on_delivery' => 'warning',
                        'mercadopago' => 'info',
                        'paypal' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
