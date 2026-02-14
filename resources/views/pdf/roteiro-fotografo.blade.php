<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pauta de Captação</title>
    <style>
        /* --- FONTES --- */
        @font-face {
            font-family: 'MaisonNeue';
            src: url('{{ public_path("fonts/Maison_Neue_Book.ttf") }}') format('truetype');
            font-weight: normal; font-style: normal;
        }
        @font-face {
            font-family: 'MaisonNeue';
            src: url('{{ public_path("fonts/Maison_Neue_Bold.ttf") }}') format('truetype');
            font-weight: bold; font-style: normal;
        }
        @font-face {
            font-family: 'MaisonNeue-Light';
            src: url('{{ public_path("fonts/Maison_Neue_Light.ttf") }}') format('truetype');
            font-weight: normal; font-style: normal;
        }

        /* GERAL */
        @page { margin: 0.6cm; }
        body { 
            font-family: 'MaisonNeue', sans-serif; 
            font-size: 10px; 
            color: #000;
            line-height: 1.2; 
        }

        /* CABEÇALHO */
        .header-container {
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
            padding-bottom: 5px;
            width: 100%;
        }
        .header-left { float: left; width: 75%; }
        .header-right { float: right; width: 25%; text-align: right; }
        .clearfix::after { content: ""; clear: both; display: table; }

        h1 { margin: 0; font-size: 16px; text-transform: uppercase; font-weight: bold; }
        .subtitle { font-family: 'MaisonNeue-Light', sans-serif; font-size: 9px; color: #555; }

        /* TABELA */
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        
        th {
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            padding: 4px;
            border-bottom: 1px solid #999;
        }

        td {
            padding: 6px 4px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        tr:last-child td { border-bottom: none; }

        /* --- ESTILOS DE ATRASO --- */
        .row-delayed { background-color: #ffe4e4; }
        .row-delayed td { border-bottom: 1px solid #f8b4b4; }
        .badge-delayed { color: #cc0000; font-weight: bold; font-size: 10px; }
        
        /* --- ESTILOS PADRÃO --- */
        .badge-prazo {
            background-color: #eee; color: #333;
            padding: 2px 5px; border-radius: 3px;
            font-weight: bold; font-size: 10px;
            display: inline-block;
        }

        .client-name { font-size: 10px; font-weight: bold; display: block; margin-bottom: 1px; }
        .canal-badge { 
            font-family: 'MaisonNeue-Light', sans-serif; 
            font-size: 9px; 
            text-transform: uppercase; 
            color: #444; 
            display: block;
            margin-top: 2px;
        }

        /* ENDEREÇOS */
        .address-container { margin: 0; padding: 0; }
        .address-box { 
            display: block; margin: 0; padding: 0; 
            line-height: 1.1; padding-bottom: 3px; /* Espaço entre endereços */
        }

        .obs-text { color: #333; font-size: 9px; line-height: 1.1; }
        .separator { color: #999; font-weight: bold; margin: 0 4px; font-size: 8px; }

        /* COLUNAS */
        .w-prazo   { width: 10%; }
        .w-cliente { width: 22%; }
        .w-end     { width: 48%; }
        .w-obs     { width: 20%; }
    </style>
</head>
<body>

    <div class="header-container clearfix">
        <div class="header-left">
            <h1>PAUTA DE CAPTAÇÃO DE CHECKING</h1>
            <div class="subtitle">Lista Operacional de Fotografia</div>
        </div>
        <div class="header-right">
            <div style="font-weight: bold;">{{ date('d/m/Y') }}</div>
            <div class="subtitle">{{ date('H:i') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="w-prazo">Prazo</th>
                <th class="w-cliente">Cliente / Canal</th>
                <th class="w-end">Endereços</th>
                <th class="w-obs">Observações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $pauta)
                @php
                    // --- LÓGICA DE ATRASO ---
                    $isDelayed = false;
                    if($pauta->prazo_captacao) {
                        $prazo = \Carbon\Carbon::parse($pauta->prazo_captacao)->startOfDay();
                        $hoje  = \Carbon\Carbon::now()->startOfDay();
                        if($prazo->lt($hoje)) {
                            $isDelayed = true;
                        }
                    }
                @endphp

            <tr class="{{ $isDelayed ? 'row-delayed' : '' }}">
                
                <td>
                    @if($pauta->prazo_captacao)
                        <span class="{{ $isDelayed ? 'badge-delayed' : 'badge-prazo' }}">
                            {{ \Carbon\Carbon::parse($pauta->prazo_captacao)->format('d/m/y') }}
                        </span>
                    @else
                        --
                    @endif
                </td>

                <td>
                    <span class="client-name">{{ $pauta->cliente }}</span>
                    <span class="canal-badge">
                        @php
                            // Lógica CORRIGIDA para o PDF (Prioriza Múltiplos, depois Importação)
                            // 1. Tenta pegar de múltiplos painéis (Edição Manual)
                            $canais = $pauta->inventarios->pluck('canal')->filter()->unique();
                            
                            if ($canais->isNotEmpty()) {
                                $canalExibicao = $canais->implode(', ');
                            } 
                            // 2. Tenta pegar do vínculo simples (Importação CSV)
                            elseif ($pauta->inventario && $pauta->inventario->canal) {
                                $canalExibicao = $pauta->inventario->canal;
                            } 
                            // 3. Fallback
                            else {
                                $canalExibicao = $pauta->canal_selecionado ?? '-';
                            }
                        @endphp
                        
                        {{ $canalExibicao }}
                    </span>
                </td>

                <td>
                    <div class="address-container">
                    @php
                        // Prioriza o campo MANUAL que guarda a lista formatada
                        $enderecoRaw = !empty($pauta->endereco_manual) 
                                        ? $pauta->endereco_manual 
                                        : ($pauta->inventario->endereco ?? '');

                        $endereco = $enderecoRaw;
                        
                        // Proteção de (S/N)
                        $endereco = preg_replace_callback('/\([^)]+\)/', function($m) {
                            return str_replace('/', '@@', $m[0]);
                        }, $endereco);
                        $endereco = str_replace(['S/ ', 'C/ ', 'N/ '], ['S@@ ', 'C@@ ', 'N@@ '], $endereco);
                        
                        // Converte barras em quebras
                        $endereco = str_replace([' / ', ' /', '/ '], "\n", $endereco);
                        
                        // Restaura barras
                        $endereco = str_replace('@@', '/', $endereco);
                        
                        // Quebra linha universal
                        $linhas = preg_split('/\r\n|\r|\n/', $endereco);
                    @endphp

                    @foreach($linhas as $linha)
                        @if(trim($linha))
                            <div class="address-box">• {{ trim($linha) }}</div>
                        @endif
                    @endforeach
                    </div>
                </td>

                <td class="obs-text">
                    @if($pauta->obs_midia && $pauta->obs_captacao)
                        {{ $pauta->obs_midia }}<span class="separator"> | </span>{{ $pauta->obs_captacao }}
                    @elseif($pauta->obs_midia)
                        {{ $pauta->obs_midia }}
                    @elseif($pauta->obs_captacao)
                        {{ $pauta->obs_captacao }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>