<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeResource\Pages;
use App\Filament\Resources\AttributeResource\RelationManagers;
use App\Models\Attribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Atributos';
    protected static ?string $modelLabel = 'atributo';
    protected static ?string $pluralModelLabel = 'atributos';
    protected static ?string $navigationGroup = 'Catálogo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('slug', Str::slug($state));
                    }),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'text' => 'Texto',
                        'number' => 'Número',
                        'color' => 'Color',
                        'select' => 'Selección',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_required')
                    ->label('Requerido')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'text' => 'Texto',
                            'number' => 'Número',
                            'color' => 'Color',
                            'select' => 'Selección',
                            default => $state
                        };
                    }),
                Tables\Columns\IconColumn::make('is_required')->label('Requerido')->boolean(),
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
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'atributo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'atributos';
    }
}
