<?php

namespace App\Filament\Resources\PrincipalResource\Pages;

use App\Filament\Resources\PrincipalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrincipal extends ViewRecord
{
    protected static string $resource = PrincipalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}