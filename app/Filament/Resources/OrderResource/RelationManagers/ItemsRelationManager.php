<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'product_name';

    protected static ?string $modelLabel = 'Item';
    
    protected static ?string $pluralModelLabel = 'Items';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $product = Product::find($state);
                        if ($product) {
                            $set('unit_price', $product->sale_price ?? $product->price);
                            $set('product_name', $product->name);
                            $set('product_sku', $product->sku);
                            self::updateTotalPrice($get, $set);
                        }
                    })
                    ->columnSpan(3),

                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotalPrice($get, $set)),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Precio Unit.')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->reactive()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotalPrice($get, $set)),
                
                Forms\Components\TextInput::make('total_price')
                    ->label('Precio Total')
                    ->numeric()
                    ->prefix('$')
                    ->readOnly(),

                Forms\Components\Hidden::make('product_name'),
                Forms\Components\Hidden::make('product_sku'),
            ])->columns(6);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product.primary_image_url')
                    ->label('Imagen')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->circular()
                    ->defaultImageUrl('/images/placeholder-product.png'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->description(fn ($record) => $record->product?->sku),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('ARS')
                    ->label('Precio Unit.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('ARS')
                    ->label('Precio Total')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Agregado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('Precio mínimo')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('max_price')
                            ->label('Precio máximo')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn (Builder $query, $price): Builder => $query->where('unit_price', '>=', $price),
                            )
                            ->when(
                                $data['max_price'],
                                fn (Builder $query, $price): Builder => $query->where('unit_price', '<=', $price),
                            );
                    })
                    ->label('Rango de Precios'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Añadir Item')
                    ->createAnother(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar item del pedido')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este item?')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar items seleccionados')
                        ->modalDescription('¿Estás seguro de que quieres eliminar los items seleccionados?'),
                ]),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(false);
    }

    private static function updateTotalPrice(Get $get, Set $set): void
    {
        $quantity = floatval($get('quantity'));
        $unitPrice = floatval($get('unit_price'));
        $set('total_price', number_format($quantity * $unitPrice, 2, '.', ''));
    }
}
