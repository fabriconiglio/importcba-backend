<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;

abstract class BaseCreateRecord extends CreateRecord
{
    /**
     * Deshabilitar la opción "Crear y crear otro" globalmente
     */
    public static function canCreateAnother(): bool  // ← STATIC
    {
        return false;
    }

    /**
     * Redireccionar al índice después de crear
     */
    protected function getCreatedRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Mensaje de éxito personalizado
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }

    /**
     * Personalizar acciones del formulario (opcional)
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}