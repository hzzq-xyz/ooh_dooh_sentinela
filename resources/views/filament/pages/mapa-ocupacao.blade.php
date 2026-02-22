<x-filament-panels::page>
    <style>
        .mapa-container {
            max-height: calc(100vh - 350px);
            overflow: auto;
            position: relative;
        }
        .mapa-table {
            font-family: 'Courier New', monospace;
            border-collapse: collapse;
            font-size: 11px;
            width: max-content;
        }
        .mapa-table th,
        .mapa-table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: center;
            white-space: nowrap;
        }
        .mapa-table th {
            background-color: #f3f4f6;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .mapa-table td.cliente-col {
            text-align: left;
            background-color: #f9fafb;
            position: sticky;
            left: 0;
            z-index: 5;
            font-weight: 500;
            min-width: 180px;
            max-width: 180px;
        }
        .mapa-table td.periodo-col {
            text-align: center;
            background-color: #f9fafb;
            font-size: 10px;
            position: sticky;
            left: 180px;
            z-index: 5;
            min-width: 90px;
        }
        .mapa-table th.cliente-header {
            position: sticky;
            left: 0;
            z-index: 15;
            background-color: #e5e7eb;
        }
        .mapa-table th.periodo-header {
            position: sticky;
            left: 180px;
            z-index: 15;
            background-color: #e5e7eb;
        }
        .mapa-table td.day-cell {
            width: 50px;
            min-width: 50px;
            max-width: 50px;
        }
        .mapa-table tr.total-row td {
            background-color: #e5e7eb !important;
            font-weight: bold;
            position: sticky;
            bottom: 0;
            z-index: 8;
        }
        .mapa-table tr.total-row td.cliente-col,
        .mapa-table tr.total-row td.periodo-col {
            z-index: 12;
        }
        .mapa-table tr:hover td:not(.total-row td) {
            background-color: #f3f4f6;
        }
        .slot-badge {
            display: inline-block;
            min-width: 22px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
            font-size: 11px;
        }
        .total-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
            font-size: 10px;
            white-space: nowrap;
        }
        .tipo-badge {
            font-size: 9px;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: 600;
        }
    </style>

    <div class="space-y-4">
        {{-- Formulário de Filtros --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- Informações do Painel --}}
        @if($canalNome)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3">
                <div class="flex items-center justify-between text-sm">
                    <div>
                        <span class="font-semibold">{{ $canalNome }}</span>
                        <span class="text-gray-500 ml-2">Capacidade: <strong>{{ $capacidadeMaxima }}</strong> slots por dia</span>
                    </div>
                    <div class="text-gray-500">
                        Veiculações: <span class="font-bold">{{ count($linhas) }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabela Estilo Planilha --}}
        @if(count($dias) > 0)
            <div class="bg-white rounded-lg shadow mapa-container">
                <table class="mapa-table">
                    <thead>
                        <tr>
                            <th class="cliente-header">CLIENTE</th>
                            <th class="periodo-header">PERÍODO</th>
                            @foreach($dias as $dia)
                                <th>{{ $dia }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($linhas as $linha)
                            <tr>
                                <td class="cliente-col">
                                    {{ $linha['cliente'] }}
                                    <span class="tipo-badge ml-1
                                        @if($linha['tipo'] == 'PAGO') bg-green-600 text-white
                                        @elseif($linha['tipo'] == 'CORTESIA') bg-blue-600 text-white
                                        @elseif($linha['tipo'] == 'PERMUTA') bg-purple-600 text-white
                                        @else bg-gray-600 text-white
                                        @endif">
                                        {{ substr($linha['tipo'], 0, 4) }}
                                    </span>
                                </td>
                                <td class="periodo-col">{{ $linha['periodo'] }}</td>
                                @foreach($dias as $dia)
                                    <td class="day-cell">
                                        @if($linha['dias'][$dia])
                                            <span class="slot-badge" style="background-color: #3B82F6;">
                                                {{ $linha['dias'][$dia] }}
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($dias) + 2 }}" class="text-center py-8 text-gray-500">
                                    Nenhuma veiculação encontrada
                                </td>
                            </tr>
                        @endforelse
                        
                        {{-- Linha de Totais --}}
                        @if(count($linhas) > 0)
                            <tr class="total-row">
                                <td class="cliente-col text-left">TOTAL OCUPADO</td>
                                <td class="periodo-col">
                                    <span class="text-xs font-bold">Ocupado/Máx</span>
                                </td>
                                @foreach($dias as $dia)
                                    @php
                                        $total = $totais[$dia];
                                        $percentual = ($capacidadeMaxima > 0) ? ($total / $capacidadeMaxima) * 100 : 0;
                                        
                                        // Determinar cor baseado na ocupação
                                        if ($total == 0) {
                                            $cor = '#10B981'; // Verde - Disponível
                                        } elseif ($total < $capacidadeMaxima) {
                                            $cor = '#F59E0B'; // Amarelo - Parcial
                                        } elseif ($total == $capacidadeMaxima) {
                                            $cor = '#EF4444'; // Vermelho - Completo
                                        } else {
                                            $cor = '#9333EA'; // Roxo - Acima da capacidade
                                        }
                                    @endphp
                                    <td class="day-cell">
                                        <span class="total-badge" 
                                            style="background-color: {{ $cor }};"
                                            title="{{ number_format($percentual, 0) }}% ocupado">
                                            {{ $total }}/{{ $capacidadeMaxima }}
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Legenda Compacta --}}
            <div class="bg-white rounded-lg shadow p-3">
                <div class="flex items-center gap-4 text-xs flex-wrap">
                    <span class="font-semibold text-gray-700">Legenda do Total (Ocupado/Máximo):</span>
                    <div class="flex items-center gap-1">
                        <span class="total-badge" style="background-color: #10B981;">0/{{ $capacidadeMaxima }}</span>
                        <span class="text-gray-600">Disponível</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="total-badge" style="background-color: #F59E0B;">X/{{ $capacidadeMaxima }}</span>
                        <span class="text-gray-600">Parcial (X &lt; {{ $capacidadeMaxima }})</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="total-badge" style="background-color: #EF4444;">{{ $capacidadeMaxima }}/{{ $capacidadeMaxima }}</span>
                        <span class="text-gray-600">Cheio</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="total-badge" style="background-color: #9333EA;">X/{{ $capacidadeMaxima }}</span>
                        <span class="text-gray-600">Acima (X &gt; {{ $capacidadeMaxima }})</span>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <h3 class="text-sm font-medium text-gray-900">Selecione um painel e período</h3>
            </div>
        @endif
    </div>
</x-filament-panels::page>