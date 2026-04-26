<?php

namespace App\Filament\Resources\PrincipalResource\Pages;

use App\Filament\Resources\PrincipalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrincipal extends EditRecord
{
    protected static string $resource = PrincipalResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
