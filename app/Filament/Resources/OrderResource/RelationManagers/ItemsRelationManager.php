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
                Tables\Columns\TextColumn::make('product.name')->label('Producto'),
                Tables\Columns\TextColumn::make('quantity')->label('Cantidad'),
                Tables\Columns\TextColumn::make('unit_price')->money('ARS')->label('Precio Unit.'),
                Tables\Columns\TextColumn::make('total_price')->money('ARS')->label('Precio Total'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Añadir Item')
                    ->createAnother(false),
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

    private static function updateTotalPrice(Get $get, Set $set): void
    {
        $quantity = floatval($get('quantity'));
        $unitPrice = floatval($get('unit_price'));
        $set('total_price', number_format($quantity * $unitPrice, 2, '.', ''));
    }
}
