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
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Código único que los clientes usarán para aplicar el descuento')
                            ->suffixIcon('heroicon-o-ticket'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Descuento 10%')
                            ->helperText('Nombre descriptivo del cupón'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Ej: 10% de descuento en toda la compra')
                            ->helperText('Descripción detallada del cupón'),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración del Descuento')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Descuento')
                            ->options([
                                'percentage' => 'Porcentaje (%)',
                                'fixed_amount' => 'Monto Fijo ($)',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'percentage') {
                                    $set('value', 10);
                                } else {
                                    $set('value', 1000);
                                }
                            }),
                        Forms\Components\TextInput::make('value')
                            ->label('Valor del Descuento')
                            ->numeric()
                            ->required()
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : '$')
                            ->helperText(fn ($get) => $get('type') === 'percentage' 
                                ? 'Porcentaje de descuento (ej: 10 para 10%)' 
                                : 'Monto fijo en pesos (ej: 1000 para $1000)'),
                        Forms\Components\TextInput::make('minimum_amount')
                            ->label('Monto Mínimo de Compra')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->helperText('Monto mínimo que debe tener el carrito para aplicar el cupón'),
                    ])->columns(3),

                Forms\Components\Section::make('Límites y Restricciones')
                    ->schema([
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Límite de Usos Totales')
                            ->numeric()
                            ->nullable()
                            ->helperText('Dejar vacío para uso ilimitado'),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Descuento Máximo')
                            ->numeric()
                            ->nullable()
                            ->prefix('$')
                            ->helperText('Límite máximo del descuento (útil para cupones de porcentaje)'),
                        Forms\Components\TextInput::make('per_user_limit')
                            ->label('Límite por Usuario')
                            ->numeric()
                            ->default(1)
                            ->helperText('Cuántas veces puede usar este cupón cada usuario'),
                    ])->columns(3),

                Forms\Components\Section::make('Vigencia')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Fecha de Inicio')
                            ->helperText('Cuándo comienza a ser válido el cupón'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de Expiración')
                            ->helperText('Cuándo expira el cupón'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->required()
                            ->default(true)
                            ->helperText('Desactivar para pausar temporalmente el cupón'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->limit(30)
                    ->wrap(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn($state) => match($state) {
                        'percentage' => 'Porcentaje',
                        'fixed_amount' => 'Monto Fijo',
                        default => $state,
                    })
                    ->color(fn($state) => match($state) {
                        'percentage' => 'success',
                        'fixed_amount' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn($state, $record) => 
                        $record->type === 'percentage' ? "{$state}%" : "$" . number_format($state, 2)
                    )
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('minimum_amount')
                    ->label('Mínimo')
                    ->money('ARS')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('Límite')
                    ->formatStateUsing(fn($state) => $state ?: '∞')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Usado')
                    ->badge()
                    ->color(fn($state, $record) => 
                        $record->usage_limit && $state >= $record->usage_limit ? 'danger' : 'success'
                    ),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inicia')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y H:i')
                    ->color(fn($state) => 
                        $state && now()->gt($state) ? 'danger' : 'success'
                    ),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Cupón')
                    ->options([
                        'percentage' => 'Porcentaje',
                        'fixed_amount' => 'Monto Fijo',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('starts_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('starts_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['starts_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['starts_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    })
                    ->label('Rango de Fechas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function (Coupon $record) {
                        $newCoupon = $record->replicate();
                        $newCoupon->code = $record->code . '_copy';
                        $newCoupon->name = $record->name . ' (Copia)';
                        $newCoupon->is_active = false;
                        $newCoupon->save();
                        
                        return redirect()->route('filament.admin.resources.coupons.edit', $newCoupon);
                    }),
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
            ->defaultSort('created_at', 'desc');
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
