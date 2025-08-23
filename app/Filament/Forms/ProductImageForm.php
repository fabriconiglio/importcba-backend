<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Storage;

class ProductImageForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Group::make([
                Forms\Components\FileUpload::make('url')
                    ->label('Imagen')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('800')
                    ->directory('products')
                    ->disk('public')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
                    ->previewable(false)
                    ->downloadable(false)
                    ->openable(false)
                    ->deletable(true)
                    ->multiple(false)
                    ->required()
                    ->helperText('Formatos permitidos: JPG, PNG, WebP, GIF. Máximo 2MB.')
                    ->columnSpan(1),
                
                Forms\Components\Placeholder::make('image_preview')
                    ->label('Vista previa actual')
                    ->content(function ($record) {
                        if ($record && $record->url) {
                            $url = Storage::url($record->url);
                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . $url . '" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px;" alt="Imagen del producto">'
                            );
                        }
                        return 'No hay imagen';
                    })
                    ->columnSpan(1)
                    ->visible(fn ($record) => $record && $record->url),
            ])->columns(2),
            
            Forms\Components\TextInput::make('alt_text')
                ->label('Texto alternativo')
                ->maxLength(255)
                ->helperText('Descripción de la imagen para accesibilidad'),
            
            Forms\Components\Group::make([
                Forms\Components\Toggle::make('is_primary')
                    ->label('Imagen principal')
                    ->helperText('Solo una imagen puede ser principal')
                    ->columnSpan(1),
                
                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->helperText('Número menor = aparece primero')
                    ->columnSpan(1),
            ])->columns(2),
        ];
    }
} 