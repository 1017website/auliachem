<?php

namespace App\Filament\Resources\SalesActivityResource\Pages;

use App\Filament\Resources\SalesActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesActivity extends EditRecord
{
    protected static string $resource = SalesActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
