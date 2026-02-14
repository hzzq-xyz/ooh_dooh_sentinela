<?php

namespace App\Filament\Resources\RenovacaoResource\Pages;

use App\Filament\Resources\RenovacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRenovacao extends EditRecord
{
    protected static string $resource = RenovacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}