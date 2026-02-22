<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Pauta;
use Illuminate\Support\Facades\DB;

class TopClientesChart extends ChartWidget
{
    use InteractsWithPageFilters; // Permite ler o filtro de data

    // CORREÇÃO V5: Removido o 'static' para permitir títulos dinâmicos
    protected ?string $heading = 'Top 5 Clientes';

    // O 'sort' CONTINUA estático na v5
    protected static ?int $sort = 5; 

    protected function getData(): array
    {
        $inicio = $this->filters['startDate'] ?? now()->startOfMonth();
        $fim    = $this->filters['endDate']   ?? now()->endOfMonth();

        $data = Pauta::query()
            ->whereBetween('data_insercao', [$inicio, $fim])
            ->select('cliente', DB::raw('count(*) as total'))
            ->groupBy('cliente')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pautas',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#8b5cf6', // Roxo (Violet)
                    'borderColor' => '#7c3aed',
                ],
            ],
            'labels' => $data->pluck('cliente')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Gráfico de barras
    }
}