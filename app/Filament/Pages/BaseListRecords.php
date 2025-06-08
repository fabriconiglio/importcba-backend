<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\ListRecords;

abstract class BaseListRecords extends ListRecords
{
    /**
     * Configuración común para todas las listas
     */
    protected function getTableFiltersFormWidth(): string
    {
        return '2xl';
    }

    /**
     * Configurar paginación por defecto
     */
    public function getDefaultTableRecordsPerPageSelectOption(): int 
    {
        return 25;
    }
}