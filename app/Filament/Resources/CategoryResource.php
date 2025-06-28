<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->maxLength(255),
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
                Forms\Components\TextInput::make('image_url')
                    ->label('URL de la imagen')
                    ->maxLength(500)
                    ->live(onBlur: true),
                Forms\Components\Placeholder::make('image_preview')
                    ->label('Vista previa de la imagen')
                    ->content(function ($get) {
                        $imageUrl = $get('image_url');
                        if (empty($imageUrl)) {
                            return new \Illuminate\Support\HtmlString('<p class="text-gray-500 text-sm">Ingresa una URL para ver la vista previa</p>');
                        }
                        return new \Illuminate\Support\HtmlString(
                            '<div class="flex items-center space-x-4">'
                            . '<img src="' . htmlspecialchars($imageUrl) . '" alt="Vista previa de la imagen" class="w-20 h-20 object-contain border border-gray-300 rounded-lg bg-white p-2" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">'
                            . '<div style="display: none;" class="w-20 h-20 border border-red-300 rounded-lg bg-red-50 flex items-center justify-center text-red-500 text-xs text-center">Error al cargar</div>'
                            . '<div class="text-sm text-gray-600"><p class="font-medium">URL: ' . (strlen($imageUrl) > 50 ? substr($imageUrl, 0, 47) . '...' : $imageUrl) . '</p><p class="text-xs mt-1">Tamaño de vista previa: 80x80px</p></div>'
                            . '</div>'
                        );
                    })
                    ->visible(fn ($get) => !empty($get('image_url'))),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
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
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl('/images/no-image.png'),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
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
            //
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
