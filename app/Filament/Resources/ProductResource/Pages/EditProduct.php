<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Redireccionar al listado después de editar conservando la posición
    protected function getRedirectUrl(): string
    {
        // Intentar obtener parámetros de la URL de referencia
        $previousUrl = url()->previous();
        $queryParams = [];
        
        // Si venimos del listado de productos, extraer los parámetros
        if (str_contains($previousUrl, $this->getResource()::getUrl('index'))) {
            $parsedUrl = parse_url($previousUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
        }
        
        // Fallback: intentar obtener desde la sesión de Filament
        if (empty($queryParams)) {
            $tableSessionKey = 'filament.admin.resources.' . str_replace(['\\', 'App\\Filament\\Resources\\', 'Resource'], ['', '', ''], static::$resource) . '.table';
            $sessionData = session()->get($tableSessionKey, []);
            
            // Construir parámetros desde los datos de sesión
            if (isset($sessionData['page'])) {
                $queryParams['page'] = $sessionData['page'];
            }
            if (isset($sessionData['tableFilters'])) {
                $queryParams['tableFilters'] = $sessionData['tableFilters'];
            }
            if (isset($sessionData['tableSearch'])) {
                $queryParams['tableSearch'] = $sessionData['tableSearch'];
            }
            if (isset($sessionData['tableSortColumn'])) {
                $queryParams['tableSortColumn'] = $sessionData['tableSortColumn'];
            }
            if (isset($sessionData['tableSortDirection'])) {
                $queryParams['tableSortDirection'] = $sessionData['tableSortDirection'];
            }
        }
        
        return $this->getResource()::getUrl('index', $queryParams);
    }
    
    // Método alternativo para conservar completamente el estado
    protected function getSavedNotificationMessage(): ?string
    {
        return 'Producto actualizado correctamente';
    }
    
}
