<?php

namespace App\Filament\Resources\PautaResource\Pages;

use App\Filament\Resources\PautaResource;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreatePauta extends CreateRecord
{
    protected static string $resource = PautaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garante que a data exista antes de calcular
        if (!empty($data['data_insercao'])) {
            $baseDate = Carbon::parse($data['data_insercao']);

            if (empty($data['prazo_captacao'])) {
                $data['prazo_captacao'] = $baseDate->copy()->addDays(2);
            }

            if (empty($data['prazo_envio'])) {
                $data['prazo_envio'] = $baseDate->copy()->addDays(5);
            }
        }

        return $data;
    }
}