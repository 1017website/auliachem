<?php

namespace App\Filament\Resources\SalesActivityResource\Pages;

use App\Filament\Resources\SalesActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesActivities extends ListRecords
{
    protected static string $resource = SalesActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->icon('heroicon-o-plus')];
    }
}