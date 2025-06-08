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
            Tables\Columns\TextColumn::make('image_url')->label('URL de la imagen'),
            Tables\Columns\TextColumn::make('alt_text')->label('Texto alternativo'),
            Tables\Columns\IconColumn::make('is_primary')->label('Principal')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->label('Orden'),
        ]);
    }
} 