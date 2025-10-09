<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ProductAttributesRelationManager;
use App\Exports\ProductsExport;
use App\Exports\ProductCreateTemplateExport;
use App\Imports\ProductsImport;
use App\Imports\ProductsCreateImport;
use App\Models\Category;
use App\Models\Brand;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $navigationGroup = 'Catálogo';

    public static function getModelLabel(): string
    {
        return 'producto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'productos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->content('**Nota:** Podrás agregar imágenes y atributos al producto después de crearlo, desde la pantalla de edición.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!empty($state)) {
                            $set('slug', \Illuminate\Support\Str::slug($state));
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
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('short_description')
                    ->label('Descripción corta')
                    ->maxLength(500),
                Forms\Components\Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->required()
                    ->nullable(),
                Forms\Components\Select::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('sale_price')
                    ->label('Precio de oferta')
                    ->numeric(),
                Forms\Components\TextInput::make('cost_price')
                    ->label('Precio de costo')
                    ->numeric(),
                Forms\Components\TextInput::make('stock_quantity')
                    ->label('Stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('min_stock_level')
                    ->label('Stock mínimo')
                    ->required()
                    ->numeric()
                    ->default(5),
                Forms\Components\TextInput::make('weight')
                    ->label('Peso')
                    ->numeric(),
                Forms\Components\TextInput::make('dimensions')
                    ->label('Dimensiones')
                    ->maxLength(100),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Destacado')
                    ->required(),
                Forms\Components\TextInput::make('meta_title')
                    ->label('Meta título')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meta_description')
                    ->label('Meta descripción')
                    ->maxLength(500),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_description')
                    ->label('Descripción corta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->prefix('$')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio de oferta')
                    ->prefix('$')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Precio de costo')
                    ->prefix('$')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_stock_level')
                    ->label('Stock mínimo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Peso')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensiones')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('meta_title')
                    ->label('Meta título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meta_description')
                    ->label('Meta descripción')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('category_id')
                    ->form([
                        Forms\Components\Select::make('categories')
                            ->label('Categorías')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(Category::all()->pluck('name', 'id'))
                            ->placeholder('Seleccionar categorías')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // MOD-031 (main): Filtro de categorías que incluye subcategorías automáticamente
                        
                        if (empty($data['categories'])) {
                            return $query;
                        }
                        
                        $selectedCategoryIds = is_array($data['categories']) ? $data['categories'] : [$data['categories']];
                        
                        // Inicializar con las categorías seleccionadas
                        $allCategoryIds = collect($selectedCategoryIds);
                        
                        // Para cada categoría seleccionada, buscar sus descendientes
                        foreach ($selectedCategoryIds as $categoryId) {
                            // Obtener subcategorías directas (nivel 1)
                            $level1Children = Category::where('parent_id', $categoryId)
                                ->pluck('id')
                                ->toArray();
                            
                            if (!empty($level1Children)) {
                                $allCategoryIds = $allCategoryIds->merge($level1Children);
                                
                                // Obtener subcategorías de segundo nivel (nivel 2)
                                foreach ($level1Children as $childId) {
                                    $level2Children = Category::where('parent_id', $childId)
                                        ->pluck('id')
                                        ->toArray();
                                    
                                    if (!empty($level2Children)) {
                                        $allCategoryIds = $allCategoryIds->merge($level2Children);
                                    }
                                }
                            }
                        }
                        
                        // Obtener array único y limpio
                        $finalCategoryIds = $allCategoryIds->unique()->filter()->values()->toArray();
                        
                        // Aplicar el filtro
                        return $query->whereIn('category_id', $finalCategoryIds);
                    }),
                    
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Seleccionar marca'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->native(false),
                    
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destacados')
                    ->boolean()
                    ->trueLabel('Solo destacados')
                    ->falseLabel('Solo no destacados')
                    ->native(false),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bajo')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('stock_quantity <= min_stock_level'))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Sin stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<=', 0))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // MOD-028 (main): Agregada acción masiva para asignar marcas a productos seleccionados
                    Tables\Actions\BulkAction::make('assign_brand')
                        ->label('Asignar Marca')
                        ->icon('heroicon-o-tag')
                        ->color('purple')
                        ->form([
                            Forms\Components\Select::make('brand_id')
                                ->label('Marca a Asignar')
                                ->options(Brand::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->placeholder('Selecciona una marca')
                                ->helperText('Se asignará esta marca a todos los productos seleccionados'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $brandId = $data['brand_id'];
                            $brand = Brand::find($brandId);
                            
                            if (!$brand) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('La marca seleccionada no existe')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $updatedCount = 0;
                            
                            foreach ($records as $product) {
                                $product->update(['brand_id' => $brandId]);
                                $updatedCount++;
                            }
                            
                            Notification::make()
                                ->title('Marcas Asignadas')
                                ->body("Se asignó la marca '{$brand->name}' a {$updatedCount} producto(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Asignar Marca a Productos Seleccionados')
                        ->modalDescription('Esta acción asignará la marca seleccionada a todos los productos que has seleccionado.')
                        ->modalSubmitActionLabel('Asignar Marca')
                        ->tooltip('Asigna una marca a todos los productos seleccionados'),
                    
                    // MOD-030 (main): Agregada acción masiva para cambiar categorías de productos
                    Tables\Actions\BulkAction::make('change_category')
                        ->label('Cambiar Categoría')
                        ->icon('heroicon-o-squares-2x2')
                        ->color('indigo')
                        ->form([
                            Forms\Components\Select::make('parent_category_id')
                                ->label('Categoría Padre')
                                ->options(Category::whereNull('parent_id')->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->placeholder('Selecciona una categoría padre')
                                ->helperText('Selecciona la categoría principal para los productos')
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('subcategory_id', null);
                                }),
                            Forms\Components\Select::make('subcategory_id')
                                ->label('Subcategoría (Opcional)')
                                ->options(function (callable $get) {
                                    $parentId = $get('parent_category_id');
                                    if (!$parentId) {
                                        return [];
                                    }
                                    return Category::where('parent_id', $parentId)->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Selecciona una subcategoría (opcional)')
                                ->helperText('Si no seleccionas subcategoría, los productos se asignarán solo a la categoría padre'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $parentCategoryId = $data['parent_category_id'];
                            $subcategoryId = $data['subcategory_id'] ?? null;
                            
                            // Determinar qué categoría asignar
                            $categoryToAssign = $subcategoryId ?: $parentCategoryId;
                            
                            $parentCategory = Category::find($parentCategoryId);
                            $subcategory = $subcategoryId ? Category::find($subcategoryId) : null;
                            
                            if (!$parentCategory) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('La categoría padre seleccionada no existe')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            if ($subcategoryId && !$subcategory) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('La subcategoría seleccionada no existe')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $updatedCount = 0;
                            
                            foreach ($records as $product) {
                                $product->update(['category_id' => $categoryToAssign]);
                                $updatedCount++;
                            }
                            
                            $categoryName = $subcategory 
                                ? "{$parentCategory->name} > {$subcategory->name}"
                                : $parentCategory->name;
                            
                            Notification::make()
                                ->title('Categorías Actualizadas')
                                ->body("Se cambió la categoría a '{$categoryName}' en {$updatedCount} producto(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar Categoría de Productos Seleccionados')
                        ->modalDescription('Esta acción cambiará la categoría de todos los productos seleccionados. Puedes asignar solo la categoría padre o también una subcategoría específica.')
                        ->modalSubmitActionLabel('Cambiar Categoría')
                        ->tooltip('Cambia la categoría padre y opcionalmente la subcategoría de los productos seleccionados'),
                ]),
            ])
            ->headerActions([
                // MOD-003 (main): Agregadas acciones de exportación e importación masiva de productos
                Tables\Actions\Action::make('export')
                    ->label('Exportar Excel Productos existentes')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->extraAttributes(['style' => 'font-size: 0.75rem; padding: 0.25rem 0.5rem;'])
                    ->form([
                        Forms\Components\Select::make('category_id')
                            ->label('Filtrar por Categoría (opcional)')
                            ->options(Category::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todas las categorías'),
                        Forms\Components\Select::make('brand_id')
                            ->label('Filtrar por Marca (opcional)')
                            ->options(Brand::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todas las marcas'),
                    ])
                    ->action(function (array $data) {
                        $filename = 'productos_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                        
                        return Excel::download(
                            new ProductsExport($data['category_id'] ?? null, $data['brand_id'] ?? null),
                            $filename
                        );
                    }),
                    
                Tables\Actions\Action::make('import')
                    ->label('Importar Excel de Productos Actualizado')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->extraAttributes(['style' => 'font-size: 0.75rem; padding: 0.25rem 0.5rem;'])
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->required()
                            ->helperText('Sube un archivo Excel (.xlsx o .xls) con las columnas: ID, Nombre, SKU, Descripción, Categoría, Marca, Precio, Precio Oferta, Stock, Stock Mínimo, Meta Título, Imagen Principal, Activo, Destacado'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new ProductsImport();
                            Excel::import($import, $data['file']);
                            
                            $stats = $import->getStats();
                            $errors = $import->getErrors();
                            
                            if ($stats['error_count'] > 0) {
                                $errorMessage = "Importación completada con errores:\n";
                                $errorMessage .= "✅ {$stats['success_count']} productos actualizados\n";
                                $errorMessage .= "❌ {$stats['error_count']} errores\n\n";
                                $errorMessage .= "Errores encontrados:\n" . implode("\n", array_slice($errors, 0, 10));
                                
                                if (count($errors) > 10) {
                                    $errorMessage .= "\n... y " . (count($errors) - 10) . " errores más.";
                                }
                                
                                Notification::make()
                                    ->title('Importación completada con errores')
                                    ->body($errorMessage)
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Importación exitosa')
                                    ->body("✅ {$stats['success_count']} productos actualizados correctamente")
                                    ->success()
                                    ->send();
                            }
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error en la importación')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('download_template')
                    ->label('Descargar Plantilla')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->extraAttributes(['style' => 'font-size: 0.75rem; padding: 0.25rem 0.5rem;'])
                    ->action(function () {
                        // Crear una plantilla vacía con solo los headers
                        $filename = 'plantilla_productos_' . now()->format('Y-m-d') . '.xlsx';
                        
                        return Excel::download(
                            new ProductsExport(null, null), // Sin filtros para obtener todos los productos como ejemplo
                            $filename
                        );
                    })
                    ->tooltip('Descarga un archivo Excel con todos los productos actuales para usar como plantilla'),
                    
                // MOD-026 (main): Agregadas acciones para crear nuevos productos desde Excel
                Tables\Actions\Action::make('create_template')
                    ->label('Plantilla Nuevos Productos')
                    ->icon('heroicon-o-plus-circle')
                    ->color('info')
                    ->extraAttributes(['style' => 'font-size: 0.75rem; padding: 0.25rem 0.5rem;'])
                    ->action(function () {
                        $filename = 'plantilla_nuevos_productos_' . now()->format('Y-m-d') . '.xlsx';
                        
                        return Excel::download(
                            new ProductCreateTemplateExport(),
                            $filename
                        );
                    })
                    ->tooltip('Descarga una plantilla con ejemplos para crear nuevos productos'),
                    
                Tables\Actions\Action::make('import_create')
                    ->label('Crear Productos desde Excel')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->extraAttributes(['style' => 'font-size: 0.75rem; padding: 0.25rem 0.5rem; background-color: #f59e0b !important;'])
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo Excel para Nuevos Productos')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->required()
                            ->helperText('Sube un archivo Excel (.xlsx o .xls) para crear nuevos productos. Usa la "Plantilla Nuevos Productos" como guía. Columnas obligatorias: NOMBRE, PRECIO.'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new ProductsCreateImport();
                            Excel::import($import, $data['file']);
                            
                            $stats = $import->getStats();
                            $errors = $import->getErrors();
                            $createdProducts = $import->getCreatedProducts();
                            
                            if ($stats['error_count'] > 0) {
                                $errorMessage = "Creación completada con errores:\n";
                                $errorMessage .= "✅ {$stats['success_count']} productos creados\n";
                                $errorMessage .= "❌ {$stats['error_count']} errores\n\n";
                                $errorMessage .= "Productos creados:\n";
                                
                                foreach (array_slice($createdProducts, 0, 5) as $product) {
                                    $errorMessage .= "• {$product['name']} (SKU: {$product['sku']}, Precio: $" . number_format($product['price'], 2) . ")\n";
                                }
                                
                                if (count($createdProducts) > 5) {
                                    $errorMessage .= "... y " . (count($createdProducts) - 5) . " productos más.\n";
                                }
                                
                                $errorMessage .= "\nErrores encontrados:\n" . implode("\n", array_slice($errors, 0, 10));
                                
                                if (count($errors) > 10) {
                                    $errorMessage .= "\n... y " . (count($errors) - 10) . " errores más.";
                                }
                                
                                Notification::make()
                                    ->title('Creación completada con errores')
                                    ->body($errorMessage)
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            } else {
                                $successMessage = "✅ {$stats['success_count']} productos creados correctamente\n\n";
                                $successMessage .= "Productos creados:\n";
                                
                                foreach (array_slice($createdProducts, 0, 10) as $product) {
                                    $successMessage .= "• {$product['name']} (SKU: {$product['sku']}, Precio: $" . number_format($product['price'], 2) . ")\n";
                                }
                                
                                if (count($createdProducts) > 10) {
                                    $successMessage .= "... y " . (count($createdProducts) - 10) . " productos más.";
                                }
                                
                                Notification::make()
                                    ->title('Productos creados exitosamente')
                                    ->body($successMessage)
                                    ->success()
                                    ->send();
                            }
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al crear productos')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    })
                    ->tooltip('Crear nuevos productos importando desde un archivo Excel'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            ProductAttributesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
