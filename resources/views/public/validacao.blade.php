<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>OPECS - Aprovação de Mídia</title>
    {{-- Usando o mesmo Tailwind CDN do sistema antigo --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6 font-sans">
    
    {{-- Lógica para buscar as URLs --}}
    @php
        $urlMidia = \Illuminate\Support\Facades\Storage::url($validacao->file_path);
        $painel = $validacao->inventario;
        // Importante: garante que a URL do mockup esteja completa
        $mockupUrl = $painel->mockup_image ? \Illuminate\Support\Facades\Storage::url($painel->mockup_image) : null;
        $isImagem = in_array(strtolower(pathinfo($validacao->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']);
    @endphp

    <div class="max-w-6xl mx-auto">
        {{-- Botão Voltar (Opcional, removi o link pois é acesso externo) --}}
        <div class="mb-4">
            <span class="bg-white border px-4 py-2 rounded-lg font-bold text-xs shadow-sm inline-block text-gray-500">
                <i class="fas fa-check-circle text-green-500"></i> Mídia Validada: {{ $painel->codigo }}
            </span>
        </div>

        <div class="bg-gray-900 rounded-2xl p-8 flex justify-center min-h-[65vh] overflow-auto shadow-2xl relative">
            
            @if($mockupUrl)
                {{-- ESTRUTURA ORIGINAL DO SEU VIEW.PHP --}}
                <div class="relative inline-block" style="height: fit-content;">
                    
                    {{-- Camada 0: O Vídeo/Imagem (Fica atrás) --}}
                    <div style="position: absolute; {{ $painel->mockup_css }}; z-index: 0; overflow: hidden; background: #000;">
                        @if($isImagem)
                            <img src="{{ $urlMidia }}" class="w-full h-full object-fill">
                        @else
                            <video autoplay loop muted playsinline class="w-full h-full object-fill">
                                <source src="{{ $urlMidia }}" type="video/mp4">
                            </video>
                        @endif
                    </div>

                    {{-- Camada 10: O Mockup (Fica na frente) --}}
                    {{-- O 'pointer-events-none' garante que cliques passem para o vídeo se precisar --}}
                    <img src="{{ $mockupUrl }}" class="relative z-10 block max-h-[75vh] pointer-events-none">
                </div>
            @else
                {{-- Fallback se não tiver mockup --}}
                <div class="flex flex-col items-center justify-center text-white opacity-40 self-center">
                    <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                    <p class="italic">Mockup não configurado para este painel.</p>
                </div>
                {{-- Mostra mídia pura --}}
                <div class="mt-4 border border-gray-600">
                     @if($isImagem)
                        <img src="{{ $urlMidia }}" class="max-h-[500px]">
                    @else
                        <video controls src="{{ $urlMidia }}" class="max-h-[500px]"></video>
                    @endif
                </div>
            @endif
        </div>

        {{-- Botão de Download Original (Estilo Antigo) --}}
        <div class="mt-6 text-center">
             <a href="{{ $urlMidia }}" download class="inline-block bg-blue-600 text-white font-bold py-3 px-8 rounded-lg text-sm shadow-md hover:bg-blue-700 transition">
                <i class="fas fa-download mr-2"></i> BAIXAR ARQUIVO ORIGINAL
            </a>
            <p class="mt-4 text-xs text-gray-400 font-bold uppercase">{{ $painel->largura_px }}x{{ $painel->altura_px }}px | {{ $painel->cidade }}</p>
        </div>
    </div>

</body>
</html>