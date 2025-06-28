<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';
    protected static ?string $recordTitleAttribute = 'image_url';
    protected static ?string $title = 'Imágenes';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('image_url')
                ->label('URL de la imagen')
                ->required(),
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
            Forms\Components\TextInput::make('alt_text')
                ->label('Texto alternativo'),
            Forms\Components\Toggle::make('is_primary')
                ->label('Principal'),
            Forms\Components\TextInput::make('sort_order')
                ->label('Orden')
                ->numeric(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Imagen')
                ->height(40)
                ->width(40)
                ->getStateUsing(fn ($record) => $record->image_url)
                ->defaultImageUrl('/images/no-image.png'),
        
            Tables\Columns\TextColumn::make('alt_text')->label('Texto alternativo'),
            Tables\Columns\IconColumn::make('is_primary')->label('Principal')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->label('Orden'),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->label('Crear imagen')
                ->modalHeading('Crear imagen')
                ->disableCreateAnother(),
        ])
        ->emptyStateHeading('No se encontraron imágenes')
        ->emptyStateDescription('Cree una imagen para este producto para empezar.')
        ->actions([
            Tables\Actions\EditAction::make()
                ->label('Editar'),
            Tables\Actions\DeleteAction::make()
                ->label('Eliminar'),
        ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
} 