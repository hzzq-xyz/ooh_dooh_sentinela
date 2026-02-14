<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Models\Validacao; // Importações sempre no topo!

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Rota para o Cliente visualizar a simulação (Link Público)
Route::get('/v/{hash}', function ($hash) {
    // Busca a validação ou dá erro 404 se não achar
    $validacao = Validacao::where('hash', $hash)->firstOrFail();
    $painel = $validacao->inventario;
    
    // Verifica se é imagem para ajustar o player
    $isImagem = in_array(strtolower(pathinfo($validacao->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']);

    return view('public.validacao', [
        'validacao' => $validacao,
        'painel'    => $painel,
        'url_midia' => Storage::url($validacao->file_path),
        'is_imagem' => $isImagem,
        'mockup_url'=> $painel->mockup_image ? Storage::url($painel->mockup_image) : null,
    ]);
})->name('validacao.publica');