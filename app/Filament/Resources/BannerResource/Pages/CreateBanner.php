<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use App\Filament\Pages\BaseCreateRecord;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBanner extends BaseCreateRecord
{
    protected static string $resource = BannerResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
