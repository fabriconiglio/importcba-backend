<?php
namespace App\Filament\Pages;

use Filament\Resources\Pages\EditRecord;

abstract class BaseEditRecord extends EditRecord
{
    /**
     * Redireccionar al índice después de actualizar
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Mensaje de éxito personalizado
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Registro actualizado exitosamente';
    }

    /**
     * Personalizar acciones del formulario
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}