<?php

namespace App\Filament\Widgets;

use App\Models\Pauta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full'; // 👈 Ocupa toda a largura
    
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // 1. Calcular Pautas em Aberto (Captação + Montagem)
        $emAberto = Pauta::whereIn('status', ['CAPTAÇÃO', 'MONTAGEM'])->count();

        // 2. Calcular Atrasadas (Prazo já passou e não está ENVIADO)
        $atrasadas = Pauta::where('status', '!=', 'ENVIADO')
            ->whereDate('prazo_captacao', '<', now())
            ->count();

        // 3. Calcular Enviadas neste Mês
        $enviadasMes = Pauta::where('status', 'ENVIADO')
            ->whereMonth('data_envio_real', now()->month)
            ->whereYear('data_envio_real', now()->year)
            ->count();

        return [
            Stat::make('Pautas em Aberto', $emAberto)
                ->description('Serviços a realizar')
                ->descriptionIcon('heroicon-m-camera')
                ->color('warning'),

            Stat::make('Atrasadas', $atrasadas)
                ->description('Prazo de captação vencido')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($atrasadas > 0 ? 'danger' : 'success'),

            Stat::make('Entregues (Mês)', $enviadasMes)
                ->description('Checking finalizado')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, $enviadasMes]), 
        ];
    }
}
