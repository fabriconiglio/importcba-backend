<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateCategory extends BaseCreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
