<?php

namespace App\Filament\Resources\SiteSettingResource\Pages;

use App\Filament\Resources\SiteSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSiteSetting extends ViewRecord
{
    protected static string $resource = SiteSettingResource::class;
    protected static ?string $title = 'Ver configuración';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Editar'),
        ];
    }
}
