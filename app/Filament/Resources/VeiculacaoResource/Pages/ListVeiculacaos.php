<?php

namespace App\Filament\Resources\VeiculacaoResource\Pages;

use App\Filament\Resources\VeiculacaoResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListVeiculacaos extends ListRecords
{
    protected static string $resource = VeiculacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}