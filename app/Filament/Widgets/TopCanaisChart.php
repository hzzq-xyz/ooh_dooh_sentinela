<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Pauta;
use Illuminate\Support\Facades\DB;

class TopCanaisChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Top 5 Canais';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Removendo filtro de data temporariamente para testar
        $data = Pauta::query()
            ->select('canal_selecionado', DB::raw('count(*) as total'))
            ->whereNotNull('canal_selecionado')
            ->where('canal_selecionado', '!=', '')
            ->groupBy('canal_selecionado')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Se não encontrar nada, retornar dados vazios
        if ($data->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Nenhum dado encontrado',
                        'data' => [0],
                        'backgroundColor' => '#3b82f6',
                    ],
                ],
                'labels' => ['Sem dados'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pautas',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                ],
            ],
            'labels' => $data->pluck('canal_selecionado')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
