<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    // Capturar parámetros cuando se carga la página de edición
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Capturar los parámetros de la URL de referencia al cargar la página
        $referer = request()->header('referer');
        if ($referer && str_contains($referer, '/admin/products')) {
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                session()->put('product_edit_return_params', $queryParams);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


    // Redireccionar al listado conservando el estado de la tabla
    protected function getRedirectUrl(): string
    {
        // 1. Intentar obtener parámetros guardados en la sesión
        $savedParams = session()->pull('product_edit_return_params', []);
        
        // 2. Si no hay parámetros guardados, intentar obtener de la URL anterior
        if (empty($savedParams)) {
            $previousUrl = url()->previous();
            if (str_contains($previousUrl, '/admin/products')) {
                $parsedUrl = parse_url($previousUrl);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $savedParams);
                }
            }
        }
        
        // 3. Si tenemos parámetros, construir la URL con ellos
        if (!empty($savedParams)) {
            return $this->getResource()::getUrl('index', $savedParams);
        }
        
        // 4. Fallback: URL simple del índice
        return $this->getResource()::getUrl('index');
    }
    
    // Mensaje de confirmación personalizado
    protected function getSavedNotificationMessage(): ?string
    {
        return 'Producto actualizado correctamente';
    }
    
}
