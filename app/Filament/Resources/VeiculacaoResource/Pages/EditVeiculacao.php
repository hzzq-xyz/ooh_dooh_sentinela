<?php

namespace App\Filament\Resources\VeiculacaoResource\Pages;

use App\Filament\Resources\VeiculacaoResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditVeiculacao extends EditRecord
{
    protected static string $resource = VeiculacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}