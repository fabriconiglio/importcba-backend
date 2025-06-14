<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductAttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'productAttributes';
    protected static ?string $title = 'Atributos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('attribute_id')
                    ->label('Atributo')
                    ->relationship('attribute', 'name')
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('attribute_id')
            ->columns([
                Tables\Columns\TextColumn::make('attribute.name')->label('Atributo'),
                Tables\Columns\TextColumn::make('value')->label('Valor'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear atributo')
                    ->modalHeading('Crear atributo')
                    ->disableCreateAnother(),
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
}
