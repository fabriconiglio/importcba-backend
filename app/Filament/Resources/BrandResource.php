<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Marcas';
    protected static ?string $modelLabel = 'marca';
    protected static ?string $pluralModelLabel = 'marcas';
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
                
                // Campo del logo con vista previa
                Forms\Components\TextInput::make('logo_url')
                    ->label('URL del logo')
                    ->maxLength(500)
                    ->live(onBlur: true),
                
                // Vista previa usando Placeholder
                Forms\Components\Placeholder::make('logo_preview')
                    ->label('Vista previa del logo')
                    ->content(function ($get) {
                        $logoUrl = $get('logo_url');
                        
                        if (empty($logoUrl)) {
                            return new HtmlString('<p class="text-gray-500 text-sm">Ingresa una URL para ver la vista previa</p>');
                        }
                        
                        return new HtmlString(
                            '<div class="flex items-center space-x-4">
                                <img 
                                    src="' . htmlspecialchars($logoUrl) . '" 
                                    alt="Vista previa del logo" 
                                    class="w-20 h-20 object-contain border border-gray-300 rounded-lg bg-white p-2"
                                    onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';"
                                >
                                <div 
                                    style="display: none;" 
                                    class="w-20 h-20 border border-red-300 rounded-lg bg-red-50 flex items-center justify-center text-red-500 text-xs text-center"
                                >
                                    Error al cargar
                                </div>
                                <div class="text-sm text-gray-600">
                                    <p class="font-medium">URL: ' . (strlen($logoUrl) > 50 ? substr($logoUrl, 0, 47) . '...' : $logoUrl) . '</p>
                                    <p class="text-xs mt-1">Tamaño de vista previa: 80x80px</p>
                                </div>
                            </div>'
                        );
                    })
                    ->visible(fn ($get) => !empty($get('logo_url'))),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                
                // Mostrar imagen en la tabla
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl('/images/no-image.png'),
                
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'marca';
    }

    public static function getPluralModelLabel(): string
    {
        return 'marcas';
    }
}