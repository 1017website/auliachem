<?php

namespace App\Filament\Resources\SalesActivityResource\Pages;

use App\Filament\Resources\SalesActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesActivity extends ViewRecord
{
    protected static string $resource = SalesActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()->icon('heroicon-o-pencil-square')];
    }
}