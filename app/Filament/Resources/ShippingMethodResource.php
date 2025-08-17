<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Filament\Resources\ShippingMethodResource\RelationManagers;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Tienda';

    protected static ?string $modelLabel = 'Método de Envío';

    protected static ?string $pluralModelLabel = 'Métodos de Envío';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Método de Envío')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej: Envío Estándar, Express, Gratis')
                            ->helperText('Nombre descriptivo del método de envío')
                            ->suffixIcon('heroicon-o-truck'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Ej: Envío a domicilio en 3-5 días hábiles')
                            ->helperText('Descripción detallada del servicio de envío')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración de Costos y Tiempos')
                    ->schema([
                        Forms\Components\TextInput::make('cost')
                            ->label('Costo del Envío')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->helperText('Costo fijo del envío'),
                        Forms\Components\TextInput::make('free_shipping_threshold')
                            ->label('Umbral para Envío Gratis')
                            ->numeric()
                            ->nullable()
                            ->prefix('$')
                            ->helperText('Monto mínimo de compra para envío gratis (dejar vacío si no aplica)'),
                        Forms\Components\TextInput::make('estimated_days')
                            ->label('Días Estimados de Entrega')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->suffix('días')
                            ->helperText('Tiempo estimado de entrega en días hábiles'),
                        Forms\Components\TextInput::make('max_weight')
                            ->label('Peso Máximo (kg)')
                            ->numeric()
                            ->nullable()
                            ->suffix('kg')
                            ->helperText('Peso máximo permitido para este método'),
                    ])->columns(2),

                Forms\Components\Section::make('Restricciones y Zonas')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->required()
                            ->default(true)
                            ->helperText('Desactivar para pausar temporalmente este método'),
                        Forms\Components\Toggle::make('is_free')
                            ->label('Envío Gratis')
                            ->helperText('Marcar si este método no tiene costo')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('cost', 0);
                                }
                            }),
                        Forms\Components\TextInput::make('priority')
                            ->label('Prioridad')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Orden de prioridad para mostrar en el checkout (1 = más alto)'),
                    ])->columns(3),
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
                Tables\Columns\TextColumn::make('cost')
                    ->label('Costo')
                    ->formatStateUsing(fn($state, $record) => 
                        $record->is_free ? 'GRATIS' : '$' . number_format($state, 2)
                    )
                    ->color(fn($state, $record) => 
                        $record->is_free ? 'success' : 'default'
                    )
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('estimated_days')
                    ->label('Entrega')
                    ->formatStateUsing(fn($state) => $state ? "{$state} días" : 'N/A')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('free_shipping_threshold')
                    ->label('Umbral Gratis')
                    ->money('ARS')
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
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Envío Gratis')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Gratis')
                    ->falseLabel('Solo Pagos'),
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
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
            'view' => Pages\ViewShippingMethod::route('/{record}'),
        ];
    }
}
