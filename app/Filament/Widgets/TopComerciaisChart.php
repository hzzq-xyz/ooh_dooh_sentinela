<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Pauta;
use Illuminate\Support\Facades\DB;

class TopComerciaisChart extends ChartWidget
{
    use InteractsWithPageFilters;

    // CORREÇÃO V5: Removido o 'static'
    protected ?string $heading = 'Top 5 Executivos (Demandas)';
    
    // O 'sort' continua estático na classe pai
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $inicio = $this->filters['startDate'] ?? now()->startOfMonth();
        $fim    = $this->filters['endDate']   ?? now()->endOfMonth();

        $data = Pauta::query()
            ->whereBetween('data_insercao', [$inicio, $fim])
            ->select('comercial', DB::raw('count(*) as total'))
            ->groupBy('comercial')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pautas Solicitadas',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#f59e0b', // Laranja
                ],
            ],
            'labels' => $data->pluck('comercial')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}