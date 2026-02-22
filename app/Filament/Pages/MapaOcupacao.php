<?php

namespace App\Filament\Pages;

use App\Models\Inventario;
use App\Models\Veiculacao;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class MapaOcupacao extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Mapa de Ocupação';
    
    protected static string|UnitEnum|null $navigationGroup = 'Mídia e Planejamento';

    public ?array $data = [];

    // Variáveis para a View
    public $dias = [];
    public $linhas = [];
    public $totais = [];
    public $capacidadeMaxima = 0;
    public $canalNome = '';
    
    public function getView(): string
    {
        return 'filament.pages.mapa-ocupacao';
    }

    public function mount(): void
    {
        $this->form->fill([
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim' => now()->endOfMonth()->format('Y-m-d'),
            'inventario_id' => Inventario::first()?->id,
            'tipo_acordo' => null,
        ]);

        $this->gerarRelatorio();
    }

    public function form(Schema $form): Schema
    {
        $inventarios = Inventario::all()->filter(function ($item) {
            return $item->codigo !== null && $item->codigo !== '';
        })->pluck('codigo', 'id')->toArray();
        
        return $form
            ->schema([
                Select::make('inventario_id')
                    ->label('Painel')
                    ->options($inventarios)
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->gerarRelatorio()),

                Select::make('tipo_acordo')
                    ->label('Filtrar Status')
                    ->options([
                        'PAGO' => 'Pago',
                        'CORTESIA' => 'Cortesia',
                        'PERMUTA' => 'Permuta',
                        'INTERNO' => 'Interno',
                    ])
                    ->placeholder('Todos')
                    ->live()
                    ->afterStateUpdated(fn () => $this->gerarRelatorio()),

                DatePicker::make('data_inicio')
                    ->label('De')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->gerarRelatorio()),

                DatePicker::make('data_fim')
                    ->label('Até')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->gerarRelatorio()),
            ])
            ->statePath('data')
            ->columns(4);
    }

    public function gerarRelatorio()
    {
        $dados = $this->form->getState();
        
        if (empty($dados['inventario_id']) || empty($dados['data_inicio']) || empty($dados['data_fim'])) {
            return;
        }

        $inicio = Carbon::parse($dados['data_inicio']);
        $fim = Carbon::parse($dados['data_fim']);
        
        if ($inicio->gt($fim)) return;

        $periodo = CarbonPeriod::create($inicio, $fim);

        $painel = Inventario::find($dados['inventario_id']);
        $this->canalNome = $painel?->canal ?? 'Desconhecido';
        $this->capacidadeMaxima = $painel->qtd_slots ?? 6; 

        $query = Veiculacao::where('inventario_id', $dados['inventario_id'])
            ->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('data_inicio', [$inicio, $fim])
                  ->orWhereBetween('data_fim', [$inicio, $fim])
                  ->orWhere(function ($sub) use ($inicio, $fim) {
                      $sub->where('data_inicio', '<', $inicio)
                          ->where('data_fim', '>', $fim);
                  });
            });

        if (!empty($dados['tipo_acordo'])) {
            $query->where('tipo_acordo', $dados['tipo_acordo']);
        }

        $veiculacoes = $query->orderBy('data_inicio')->get();

        $this->dias = [];
        $this->linhas = [];
        $this->totais = [];

        foreach ($periodo as $data) {
            $diaStr = $data->format('d/m');
            $this->dias[] = $diaStr;
            $this->totais[$diaStr] = 0;
        }

        foreach ($veiculacoes as $v) {
            $vInicio = Carbon::parse($v->data_inicio);
            $vFim = Carbon::parse($v->data_fim);
            
            $ocupacaoDias = array_fill_keys($this->dias, null);

            foreach ($periodo as $data) {
                if ($data->between($vInicio, $vFim)) {
                    $diaStr = $data->format('d/m');
                    $ocupacaoDias[$diaStr] = $v->slots;
                    $this->totais[$diaStr] += $v->slots;
                }
            }

            $this->linhas[] = [
                'id' => $v->id,
                'cliente' => $v->cliente,
                'tipo' => $v->tipo_acordo,
                'periodo' => $vInicio->format('d/m') . ' a ' . $vFim->format('d/m'),
                'dias' => $ocupacaoDias,
            ];
        }
    }
}