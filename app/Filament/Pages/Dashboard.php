<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopCanaisChart;
use App\Filament\Widgets\TopComerciaisChart;
use App\Filament\Widgets\PerformanceEntregaChart;
use App\Filament\Widgets\TopClientesChart;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Visão Geral';
    
    protected static ?string $navigationLabel = 'Dashboard';

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Filtros de Período')
                    ->description('Selecione o período para visualizar os dados')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Data Inicial')
                            ->default(now()->startOfMonth())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required(),
                        
                        DatePicker::make('endDate')
                            ->label('Data Final')
                            ->default(now()->endOfMonth())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->after('startDate'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            TopCanaisChart::class,
            TopComerciaisChart::class,
            PerformanceEntregaChart::class,
            TopClientesChart::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3; // 👈 Mudei de 2 para 3 colunas!
    }
}
