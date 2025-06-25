<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\RoleResource;

class CreateRole extends BaseCreateRecord
{
    protected static string $resource = RoleResource::class;

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
