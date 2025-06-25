<?php

namespace App\Filament\Resources\SiteSettingResource\Pages;

use App\Filament\Resources\SiteSettingResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateSiteSetting extends BaseCreateRecord
{
    protected static string $resource = SiteSettingResource::class;
    protected static ?string $title = 'Crear configuración';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Método específico para creación (por compatibilidad)
    protected function getCreatedRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
