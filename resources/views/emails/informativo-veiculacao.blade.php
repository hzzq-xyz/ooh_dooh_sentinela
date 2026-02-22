<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informativo de Veiculação</title>
</head>
<body style="margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 900px; margin: 0 auto; background-color: white; border: 2px solid #e0e0e0;">
        
        {{-- Header com Logo --}}
        <div style="padding: 20px; border-bottom: 2px solid #e0e0e0;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="background-color: #dc2626; width: 60px; height: 60px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                        <text x="12" y="17" text-anchor="middle" fill="white" font-size="14" font-weight="bold">i</text>
                    </svg>
                </div>
                <div>
                    <div style="color: #dc2626; font-size: 18px; font-weight: bold; margin: 0;">Informativo</div>
                    <div style="color: #dc2626; font-size: 18px; font-weight: bold; margin: 0;">de veiculação</div>
                </div>
            </div>
        </div>

        {{-- Banner Vermelho --}}
        <div style="background-color: #dc2626; color: white; text-align: center; padding: 15px; font-size: 20px; font-weight: bold; letter-spacing: 2px;">
            INFORMATIVO DIGITAL
        </div>

        {{-- Tabela de Informações --}}
        <table style="width: 100%; border-collapse: collapse; margin: 0;">
            <tr style="background-color: #f3f4f6;">
                <td style="padding: 12px 20px; font-weight: bold; text-align: right; width: 30%; border: 1px solid #e0e0e0;">
                    CLIENTE
                </td>
                <td style="padding: 12px 20px; border: 1px solid #e0e0e0; font-weight: bold; text-transform: uppercase;">
                    {{ $nomeCliente }}
                </td>
            </tr>

            <tr>
                <td style="padding: 12px 20px; font-weight: bold; text-align: right; background-color: #f3f4f6; border: 1px solid #e0e0e0;">
                    CAMPANHA
                </td>
                <td style="padding: 12px 20px; border: 1px solid #e0e0e0; font-weight: bold; text-transform: uppercase;">
                    {{ $nomeCampanha }}
                </td>
            </tr>

            <tr style="background-color: #f3f4f6;">
                <td style="padding: 12px 20px; font-weight: bold; text-align: right; border: 1px solid #e0e0e0;">
                    ATENDIMENTO
                </td>
                <td style="padding: 12px 20px; border: 1px solid #e0e0e0; font-weight: bold; text-transform: uppercase;">
                    {{ $atendimento }}
                </td>
            </tr>

            {{-- MÚLTIPLAS PLATAFORMAS --}}
            <tr>
                <td style="padding: 12px 20px; font-weight: bold; text-align: right; background-color: #f3f4f6; border: 1px solid #e0e0e0; vertical-align: top;">
                    PLATAFORMA{{ $veiculacoes->count() > 1 ? 'S' : '' }}
                </td>
                <td style="padding: 12px 20px; border: 1px solid #e0e0e0;">
                    @foreach($veiculacoes as $index => $veiculacao)
                        <div style="margin-bottom: {{ $loop->last ? '0' : '15px' }}; padding-bottom: {{ $loop->last ? '0' : '15px' }}; border-bottom: {{ $loop->last ? 'none' : '1px solid #e5e7eb' }};">
                            {{-- Numeração se houver múltiplos --}}
                            @if($veiculacoes->count() > 1)
                                <div style="font-weight: bold; color: #dc2626; margin-bottom: 5px;">{{ $index + 1 }}.</div>
                            @endif
                            
                            {{-- Nome do Canal --}}
                            @if($veiculacao->inventario)
                                <div style="font-weight: bold; font-size: 14px; margin-bottom: 5px;">
                                    {{ $veiculacao->inventario->canal }}
                                </div>
                                
                                {{-- Endereço --}}
                                @if($veiculacao->inventario->endereco)
                                    <div style="font-size: 13px; color: #666; margin-bottom: 3px;">
                                        AV. {{ strtoupper($veiculacao->inventario->endereco) }}
                                    </div>
                                @endif
                                
                                {{-- Bairro e Cidade --}}
                                @if($veiculacao->inventario->bairro || $veiculacao->inventario->cidade)
                                    <div style="font-size: 13px; color: #666; margin-bottom: 3px;">
                                        @if($veiculacao->inventario->bairro)
                                            {{ $veiculacao->inventario->bairro }}
                                        @endif
                                        @if($veiculacao->inventario->bairro && $veiculacao->inventario->cidade)
                                            , 
                                        @endif
                                        @if($veiculacao->inventario->cidade)
                                            {{ $veiculacao->inventario->cidade }}
                                        @endif
                                    </div>
                                @endif
                                
                                {{-- Dimensões se existir --}}
                                @if($veiculacao->inventario->dimensoes_fisicas)
                                    <div style="font-size: 12px; color: #888; margin-top: 3px;">
                                        {{ $veiculacao->inventario->dimensoes_fisicas }}
                                    </div>
                                @endif
                            @else
                                <div style="font-weight: bold;">PAINEL NÃO ESPECIFICADO</div>
                            @endif
                        </div>
                    @endforeach
                </td>
            </tr>

            {{-- FREQUÊNCIA TOTAL --}}
            <tr style="background-color: #f3f4f6;">
                <td style="padding: 12px 20px; font-weight: bold; text-align: right; border: 1px solid #e0e0e0;">
                    FREQUÊNCIA TOTAL
                </td>
                <td style="padding: 12px 20px; border: 1px solid #e0e0e0; font-weight: bold;">
                    {{ $veiculacoes->sum('slots') }} SLOT{{ $veiculacoes->sum('slots') > 1 ? 'S' : '' }}
                    @if($veiculacoes->count() > 1)
                        <span style="font-size: 13px; color: #666; font-weight: normal;">
                            ({{ $veiculacoes->count() }} {{ $veiculacoes->count() > 1 ? 'painéis' : 'painel' }})
                        </span>
                    @endif
                </td>
            </tr>

            {{-- PERÍODO --}}
            <tr>
                <td style="padding: 12px 20px; font-weight: bold; text-align: right; background-color: #f3f4f6; border: 1px solid #e0e0e0;">
                    PERÍODO
                </td>
                <td style="padding: 12px 20px; border: 1px solid #e0e0e0; font-weight: bold;">
                    {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} A {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        {{-- Imagem da Campanha (se existir) --}}
        @if($imagemCampanha)
            <div style="padding: 30px 20px; text-align: center; background-color: #f9fafb;">
                <img src="{{ $imagemCampanha }}" 
                     alt="Campanha" 
                     style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            </div>
        @endif

        {{-- Footer --}}
        <div style="padding: 20px; background-color: #f9fafb; border-top: 2px solid #e0e0e0; text-align: center; color: #666; font-size: 12px;">
            <p style="margin: 5px 0;">Este é um informativo automático gerado pelo sistema NELA</p>
            <p style="margin: 5px 0;">Para mais informações, entre em contato conosco</p>
            @if($veiculacoes->count() > 1)
                <p style="margin: 10px 0 5px 0; font-weight: bold; color: #dc2626;">
                    Total de {{ $veiculacoes->count() }} plataformas nesta campanha
                </p>
            @endif
        </div>

    </div>
</body>
</html>
