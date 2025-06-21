<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Model;

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
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('attribute.name')->label('Atributo'),
                Tables\Columns\TextColumn::make('attributeValue.value')->label('Valor'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear atributo')
                    ->modalHeading('Crear atributo')
                    ->disableCreateAnother()
                    ->using(function (array $data, RelationManager $livewire): Model {
                        $attributeValue = AttributeValue::firstOrCreate(
                            ['attribute_id' => $data['attribute_id'], 'value' => $data['value']]
                        );
                        
                        $dataForCreate = [
                            'attribute_id' => $data['attribute_id'],
                            'attribute_value_id' => $attributeValue->id
                        ];

                        return $livewire->getRelationship()->create($dataForCreate);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar atributo')
                    ->fillForm(function (Model $record): array {
                        return [
                            'attribute_id' => $record->attribute_id,
                            'value' => $record->attributeValue->value,
                        ];
                    })
                    ->using(function (Model $record, array $data): Model {
                        $attributeValue = AttributeValue::firstOrCreate(
                           ['attribute_id' => $data['attribute_id'], 'value' => $data['value']]
                       );
                       
                       $dataForUpdate = [
                           'attribute_id' => $data['attribute_id'],
                           'attribute_value_id' => $attributeValue->id,
                       ];

                       $record->update($dataForUpdate);
                       return $record;
                   }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
