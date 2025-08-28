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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

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
                    ->maxLength(255)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!empty($state)) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->extraInputAttributes([
                        'autocomplete' => 'off',
                        'spellcheck' => 'false',
                    ]),
                
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true) // Evitar conflictos de unicidad
                    ->extraInputAttributes([
                        'autocomplete' => 'off',
                        'spellcheck' => 'false',
                    ]),
                
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->extraInputAttributes([
                        'autocomplete' => 'off',
                    ]),
                
                Forms\Components\Group::make([
                    Forms\Components\FileUpload::make('logo_url')
                        ->label('Logo')
                        ->directory('brands')
                        ->disk('public')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'])
                        ->previewable(false)
                        ->downloadable(false)
                        ->openable(false)
                        ->deletable(true)
                        ->multiple(false)
                        ->helperText('Logo de la marca. Máximo 2MB. Formatos: JPG, PNG, WebP, GIF, SVG')
                        ->columnSpan(1),
                    
                    Forms\Components\Placeholder::make('logo_preview')
                        ->label('Vista previa actual')
                        ->content(function ($record) {
                            if ($record && $record->logo_url) {
                                $url = Storage::url($record->logo_url);
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . $url . '" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px;" alt="Logo de marca">'
                                );
                            }
                            return 'No hay logo';
                        })
                        ->columnSpan(1)
                        ->visible(fn ($record) => $record && $record->logo_url),
                ])->columns(2),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ])
            ->columns(1) // Mejorar el layout en una sola columna
            ->statePath('data'); // Optimizar el manejo del estado
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withProductsCount())
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                
                // Mostrar imagen en la tabla
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl('/images/no-image.png'),
                
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state <= 5 => 'success',
                        $state <= 20 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Actualizado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar marca?')
                    ->modalDescription(function (Brand $record) {
                        $productCount = $record->products()->count();
                        if ($productCount > 0) {
                            return "Esta marca tiene {$productCount} producto(s) asociado(s). Al eliminarla, los productos quedarán sin marca asignada.";
                        }
                        return '¿Estás seguro de que quieres eliminar esta marca?';
                    })
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Brand $record) {
                        try {
                            $productCount = $record->products()->count();
                            $record->delete();
                            
                            if ($productCount > 0) {
                                Notification::make()
                                    ->title('Marca eliminada')
                                    ->body("La marca se eliminó correctamente. {$productCount} producto(s) quedaron sin marca asignada.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Marca eliminada')
                                    ->body('La marca se eliminó correctamente.')
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al eliminar')
                                ->body('No se pudo eliminar la marca. Verifica que no tenga productos asociados.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Brand $record) => $record->products()->count() === 0 || true), // Mostrar siempre para permitir eliminación
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar'),
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