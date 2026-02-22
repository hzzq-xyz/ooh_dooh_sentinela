<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Pauta;

class PerformanceEntregaChart extends ChartWidget
{
    use InteractsWithPageFilters;

    /**
     * No Filament v5:
     * - heading: NÃO é estático (permite títulos dinâmicos).
     * - sort: CONTINUA estático.
     */
    protected ?string $heading = 'Performance de Entrega'; // Sem 'static'
    
    protected static ?int $sort = 4; // MANTENHA o 'static'

    protected function getData(): array
    {
        $inicio = $this->filters['startDate'] ?? now()->startOfMonth();
        $fim    = $this->filters['endDate']   ?? now()->endOfMonth();

        $query = Pauta::query()
            ->where('status', 'ENVIADO')
            ->whereNotNull('data_envio_real')
            ->whereBetween('data_envio_real', [$inicio, $fim]);

        $adiantados = (clone $query)->whereColumn('data_envio_real', '<', 'prazo_envio')->count();
        $noPrazo    = (clone $query)->whereColumn('data_envio_real', '=', 'prazo_envio')->count();
        $atrasados  = (clone $query)->whereColumn('data_envio_real', '>', 'prazo_envio')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Entregas',
                    'data' => [$adiantados, $noPrazo, $atrasados],
                    'backgroundColor' => [
                        '#10b981', // Verde
                        '#3b82f6', // Azul
                        '#ef4444', // Vermelho
                    ],
                ],
            ],
            'labels' => ['Adiantado', 'No Dia', 'Atrasado'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}