<?php

namespace App\Filament\Resources\ShippingMethodResource\Pages;

use App\Filament\Resources\ShippingMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShippingMethod extends ViewRecord
{
    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Volver')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
            Actions\EditAction::make(),
        ];
    }
}
