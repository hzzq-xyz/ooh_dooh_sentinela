<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Adicionei esta linha para evitar erros

class Inventario extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'iluminado' => 'boolean',
        'impactos' => 'integer',
        'largura_px' => 'integer',
        'altura_px' => 'integer',
        'tempo_maximo' => 'integer',
        'qtd_slots' => 'integer',
    ];

    // --- AQUI ESTÁ A PARTE NOVA ---
    public function validacoes(): HasMany
    {
        return $this->hasMany(Validacao::class);
    }
}