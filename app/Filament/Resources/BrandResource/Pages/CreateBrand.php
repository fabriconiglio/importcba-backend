<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateBrand extends BaseCreateRecord
{
    protected static string $resource = BrandResource::class;

    // Método principal para redirección después de crear
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