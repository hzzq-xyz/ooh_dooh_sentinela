<?php

namespace App\Filament\Resources\Pautas\Pages;

use App\Filament\Resources\Pautas\PautaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPauta extends ViewRecord
{
    protected static string $resource = PautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
