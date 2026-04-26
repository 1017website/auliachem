<?php

namespace App\Filament\Resources\PrincipalResource\Pages;

use App\Filament\Resources\PrincipalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrincipals extends ListRecords
{
    protected static string $resource = PrincipalResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
