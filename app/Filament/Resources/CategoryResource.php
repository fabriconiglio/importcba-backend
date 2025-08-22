<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Categorías';
    protected static ?string $modelLabel = 'categoría';
    protected static ?string $pluralModelLabel = 'categorías';
    protected static ?string $navigationGroup = 'Catálogo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('slug', Str::slug($state));
                    }),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción'),
                Forms\Components\Select::make('parent_id')
                    ->label('Categoría padre')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Group::make([
                    Forms\Components\FileUpload::make('image_url')
                        ->label('Imagen')
                        ->directory('categories')
                        ->disk('public')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->previewable(false)
                        ->downloadable(false)
                        ->openable(false)
                        ->deletable(true)
                        ->multiple(false)
                        ->helperText('Imagen para la categoría. Máximo 2MB. Formatos: JPG, PNG, WebP')
                        ->columnSpan(1),
                    
                    Forms\Components\Placeholder::make('image_preview')
                        ->label('Vista previa actual')
                        ->content(function ($record) {
                            if ($record && $record->image_url) {
                                $url = Storage::url($record->image_url);
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . $url . '" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px;" alt="Imagen de categoría">'
                                );
                            }
                            return 'No hay imagen';
                        })
                        ->columnSpan(1)
                        ->visible(fn ($record) => $record && $record->image_url),
                ])->columns(2),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
                
                Forms\Components\Section::make('Marcas Asociadas')
                    ->description('Selecciona las marcas que pertenecen a esta categoría')
                    ->schema([
                        Forms\Components\CheckboxList::make('brands')
                            ->label('Marcas')
                            ->relationship('brands', 'name')
                            ->options(Brand::where('is_active', true)->pluck('name', 'id'))
                            ->columns(3)
                            ->searchable()
                            ->bulkToggleable()
                            ->gridDirection('row')
                            ->hint('Puedes seleccionar múltiples marcas para esta categoría'),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Categoría padre')->sortable(),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Imagen')
                    ->disk('public')
                    ->height(30)
                    ->width(30)
                    ->square()
                    ->defaultImageUrl('/images/no-image.png')
                    ->extraAttributes(['loading' => 'lazy', 'style' => 'object-fit: cover;'])
                    ->checkFileExistence(false)
                    ->visibility('public'), // Especificar visibilidad
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('brands_count')
                    ->label('Marcas')
                    ->counts('brands')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Orden')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Actualizado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            RelationManagers\BrandsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'categoría';
    }

    public static function getPluralModelLabel(): string
    {
        return 'categorías';
    }
}
