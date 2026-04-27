<?php

namespace App\Filament\Resources\SalesLeadResource\Pages;

use App\Filament\Resources\SalesLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesLead extends ViewRecord
{
    protected static string $resource = SalesLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}