<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            // Nenhuma ação - logs são read-only
        ];
    }
}