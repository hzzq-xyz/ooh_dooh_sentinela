<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Inventario;
use App\Models\Validacao;

/*
|--------------------------------------------------------------------------
| API para o Validador Externo
|--------------------------------------------------------------------------
*/

// 1. Fornece a lista de painéis para o Validador (select)
Route::get('/paineis/configuracoes', function () {
    return Inventario::where('tipo', 'On')->get()->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->codigo, // O nome que aparece na lista
            'w' => (int) $p->largura_px,
            'h' => (int) $p->altura_px,
            'd' => (int) ($p->tempo_maximo ?? 15),
            // Gera a URL completa da imagem do mockup para o validador usar
            'mockup_img' => $p->mockup_image ? url('storage/' . $p->mockup_image) : null,
            'mockup_css' => $p->mockup_css
        ];
    });
});

// 2. Recebe o aviso de que uma validação foi feita (Webhook)
Route::post('/webhook/media-receiver', function (Request $request) {
    // Aqui salvamos o link no banco do Laravel para você ter o histórico
    // O validador manda: file_url, slot_id, panel_config...
    
    // Tenta achar o painel pelo nome
    $painel = Inventario::where('codigo', $request->input('panel_config'))->first();
    
    if ($painel) {
        Validacao::create([
            'inventario_id' => $painel->id,
            'file_path' => $request->input('file_url'), // Aqui salvamos a URL completa do validador
            'hash' => $request->input('slot_id'), // Usamos o ID do upload como hash
        ]);
    }

    return response()->json(['status' => 'success']);
});