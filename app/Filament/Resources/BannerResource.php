<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Filament\Resources\BannerResource\RelationManagers;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationGroup = 'Configuración';
    
    protected static ?string $modelLabel = 'Banner';
    
    protected static ?string $pluralModelLabel = 'Banners';
    
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Información del Banner')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Título')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ej: Oferta Especial'),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción')
                                ->rows(3)
                                ->maxLength(500)
                                ->placeholder('Descripción del banner (opcional)'),
                            
                            Forms\Components\Section::make('Imágenes del Banner')
                                ->description('Sube una imagen para desktop y opcionalmente una optimizada para móvil')
                                ->schema([
                                    Forms\Components\Group::make([
                                        Forms\Components\FileUpload::make('image_url')
                                            ->label('Imagen Desktop')
                                            ->directory('banners/desktop')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->image()
                                            ->imageResizeMode('contain')
                                            ->imageCropAspectRatio('16:9')
                                            ->imageResizeTargetWidth(1200)
                                            ->imageResizeTargetHeight(675)
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'])
                                            ->previewable(false)
                                            ->downloadable(false)
                                            ->openable(false)
                                            ->deletable(true)
                                            ->multiple(false)
                                            ->required()
                                            ->helperText('Recomendado: 1200x675px o proporción 16:9. Máximo 2MB.')
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('desktop_preview')
                                            ->label('Vista previa Desktop')
                                            ->content(function ($record) {
                                                if ($record && $record->image_url) {
                                                    $url = Storage::url($record->image_url);
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<img src="' . $url . '" style="max-width: 300px; max-height: 168px; object-fit: cover; border-radius: 8px;" alt="Banner Desktop">'
                                                    );
                                                }
                                                return 'No hay imagen desktop';
                                            })
                                            ->columnSpan(1)
                                            ->visible(fn ($record) => $record && $record->image_url),
                                    ])->columns(2),

                                    Forms\Components\Group::make([
                                        Forms\Components\FileUpload::make('mobile_image_url')
                                            ->label('Imagen Móvil (Opcional)')
                                            ->directory('banners/mobile')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->image()
                                            ->imageResizeMode('contain')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth(800)
                                            ->imageResizeTargetHeight(800)
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'])
                                            ->previewable(false)
                                            ->downloadable(false)
                                            ->openable(false)
                                            ->deletable(true)
                                            ->multiple(false)
                                            ->helperText('Recomendado: 800x800px o proporción 1:1. Si no se proporciona, se usará la imagen desktop.')
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('mobile_preview')
                                            ->label('Vista previa Móvil')
                                            ->content(function ($record) {
                                                if ($record && $record->mobile_image_url) {
                                                    $url = Storage::url($record->mobile_image_url);
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<img src="' . $url . '" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px;" alt="Banner Móvil">'
                                                    );
                                                }
                                                return 'No hay imagen móvil';
                                            })
                                            ->columnSpan(1)
                                            ->visible(fn ($record) => $record && $record->mobile_image_url),
                                    ])->columns(2),
                                ])->columnSpanFull(),
                        ])->columns(1),
                ])->columnSpan(['lg' => 2]),
                
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Configuración del Enlace')
                        ->schema([
                            Forms\Components\TextInput::make('link_url')
                                ->label('URL del Enlace')
                                ->url()
                                ->placeholder('https://ejemplo.com')
                                ->helperText('URL a la que dirigirá el banner (opcional)'),
                            
                            Forms\Components\TextInput::make('link_text')
                                ->label('Texto del Botón')
                                ->maxLength(50)
                                ->placeholder('Ver Ofertas')
                                ->helperText('Texto del botón de acción (opcional)'),
                        ]),
                    
                    Forms\Components\Section::make('Configuración de Visualización')
                        ->schema([
                            Forms\Components\TextInput::make('sort_order')
                                ->label('Orden de Visualización')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->helperText('Orden en que aparecerá el banner (menor número = primera posición)'),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Banner Activo')
                                ->default(true)
                                ->helperText('Define si el banner se muestra en el sitio web'),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Desktop')
                    ->height(50)
                    ->width(90)
                    ->defaultImageUrl(url('/images/placeholder-banner.png'))
                    ->extraAttributes(['style' => 'object-fit: cover; border-radius: 6px;'])
                    ->checkFileExistence(false)
                    ->visibility('public'),
                
                Tables\Columns\ImageColumn::make('mobile_image_url')
                    ->label('Móvil')
                    ->height(50)
                    ->width(50)
                    ->defaultImageUrl(null)
                    ->extraAttributes(['style' => 'object-fit: cover; border-radius: 6px;'])
                    ->checkFileExistence(false)
                    ->visibility('public')
                    ->placeholder('Sin imagen móvil')
                    ->tooltip('Imagen optimizada para móvil'),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->color('success'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
