<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $modelLabel = 'Cupón';

    protected static ?string $pluralModelLabel = 'Cupones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ej: Descuento 10%'),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->placeholder('Ej: 10% de descuento en toda la compra'),
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'percentage' => 'Porcentaje',
                        'fixed_amount' => 'Monto Fijo',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('minimum_amount')
                    ->label('Monto Mínimo de Compra')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('usage_limit')
                    ->label('Límite de Usos Totales')
                    ->numeric()
                    ->nullable(),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Fecha de Inicio'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Fecha de Expiración'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('description')->label('Descripción')->limit(50)->wrap(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')
                    ->formatStateUsing(fn($state) => $state === 'percentage' ? 'Porcentaje' : ($state === 'fixed_amount' ? 'Monto Fijo' : $state)),
                Tables\Columns\TextColumn::make('value')->label('Valor')->money('ARS'),
                Tables\Columns\TextColumn::make('usage_limit')->label('Límite Usos'),
                Tables\Columns\TextColumn::make('used_count')->label('Usado'),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('expires_at')->label('Expira')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
            'view' => Pages\ViewCoupon::route('/{record}'),
        ];
    }
}
