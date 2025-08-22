<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrandsRelationManager extends RelationManager
{
    protected static string $relationship = 'brands';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('brand_id')
                    ->label('Marca')
                    ->options(Brand::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la marca')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción'),
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->directory('brands')
                            ->disk('public'),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Brand::create($data)->id;
                    }),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Marca destacada')
                    ->helperText('Las marcas destacadas se mostrarán primero en el mega menú')
                    ->default(false),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden de visualización')
                    ->numeric()
                    ->default(0)
                    ->helperText('Número para ordenar las marcas (menor número = mayor prioridad)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->disk('public')
                    ->height(30)
                    ->width(30),
                Tables\Columns\IconColumn::make('pivot.is_featured')
                    ->label('Destacada')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('pivot.sort_order')
                    ->label('Orden')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('pivot.is_featured')
                    ->label('Marcas destacadas')
                    ->trueLabel('Solo destacadas')
                    ->falseLabel('Solo no destacadas')
                    ->placeholder('Todas'),
            ])
            ->defaultSort('pivot.sort_order')
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Asociar marca')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Marca')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Marca destacada')
                            ->default(false),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                    ])
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->form([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Marca destacada'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric(),
                    ]),
                Tables\Actions\DetachAction::make()
                    ->label('Desasociar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Desasociar seleccionadas'),
                ]),
            ]);
    }
}
