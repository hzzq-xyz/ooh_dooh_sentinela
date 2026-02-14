<?php

namespace App\Filament\Resources\PautaResource\Pages;

use App\Filament\Resources\PautaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPauta extends EditRecord
{
    protected static string $resource = PautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}