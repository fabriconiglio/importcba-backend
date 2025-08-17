<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Form;

class ProductImageForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\FileUpload::make('image')
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
                ->required()
                ->helperText('Formatos permitidos: JPG, PNG, GIF. Máximo 2MB.'),
            
            Forms\Components\TextInput::make('alt_text')
                ->label('Texto alternativo')
                ->maxLength(255)
                ->helperText('Descripción de la imagen para accesibilidad'),
            
            Forms\Components\Toggle::make('is_primary')
                ->label('Imagen principal')
                ->helperText('Solo una imagen puede ser principal'),
            
            Forms\Components\TextInput::make('sort_order')
                ->label('Orden')
                ->numeric()
                ->default(0)
                ->helperText('Número menor = aparece primero'),
        ];
    }
} 