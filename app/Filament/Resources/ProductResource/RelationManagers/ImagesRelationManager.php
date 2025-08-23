<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Support\Facades\Http;
use App\Filament\Forms\ProductImageForm;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';
    protected static ?string $recordTitleAttribute = 'url';
    protected static ?string $title = 'Imágenes del Producto';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema(ProductImageForm::schema());
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                ImageColumn::make('url')
                    ->label('Imagen')
                    ->disk('public')
                    ->height(80)
                    ->width(80)
                    ->square()
                    ->defaultImageUrl('/images/placeholder-product.png'),
                
                TextColumn::make('alt_text')
                    ->label('Texto alternativo')
                    ->limit(30)
                    ->searchable(),
                
                IconColumn::make('is_primary')
                    ->label('Principal')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->badge(),
                
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar imagen')
                    ->modalHeading('Agregar nueva imagen')
                    ->modalDescription('Sube una imagen para este producto')
                    ->createAnother(false)
                    ->successNotificationTitle('Imagen agregada correctamente'),
            ])
            ->actions([
                Action::make('setPrimary')
                    ->label('Hacer principal')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Establecer como imagen principal')
                    ->modalDescription('¿Estás seguro de que quieres establecer esta imagen como principal?')
                    ->modalSubmitActionLabel('Sí, establecer como principal')
                    ->action(function ($record) {
                        $record->update(['is_primary' => true]);
                        $record->product->images()
                            ->where('id', '!=', $record->id)
                            ->update(['is_primary' => false]);
                    })
                    ->visible(fn ($record) => !$record->is_primary),
                
                EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar imagen')
                    ->successNotificationTitle('Imagen actualizada correctamente'),
                
                DeleteAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Eliminar imagen')
                    ->modalDescription('¿Estás seguro de que quieres eliminar esta imagen? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->successNotificationTitle('Imagen eliminada correctamente')
                    ->action(function ($record) {
                        // Si es la imagen principal, asignar la primera disponible como principal
                        if ($record->is_primary) {
                            $firstImage = $record->product->images()
                                ->where('id', '!=', $record->id)
                                ->orderBy('sort_order')
                                ->first();
                            if ($firstImage) {
                                $firstImage->update(['is_primary' => true]);
                            }
                        }
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas')
                        ->modalHeading('Eliminar imágenes seleccionadas')
                        ->modalDescription('¿Estás seguro de que quieres eliminar las imágenes seleccionadas?')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->successNotificationTitle('Imágenes eliminadas correctamente'),
                ]),
            ])
            ->emptyStateHeading('No hay imágenes')
            ->emptyStateDescription('Agrega la primera imagen para este producto.')
            ->emptyStateIcon('heroicon-o-photo')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Agregar primera imagen')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public function canCreate(): bool
    {
        return true;
    }

    public function canEdit($record): bool
    {
        return true;
    }

    public function canDelete($record): bool
    {
        return true;
    }
} 