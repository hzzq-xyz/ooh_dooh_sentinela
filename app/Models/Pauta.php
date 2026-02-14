<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pauta extends Model
{
    protected $guarded = []; // Libera todos os campos para escrita

    protected $casts = [
        'data_insercao' => 'date',
        'prazo_captacao' => 'date',
        'prazo_envio' => 'date',
        'data_captacao' => 'date',
        'data_envio_real' => 'date',
    ];

    // Relacionamento principal (um painel específico)
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    // Relacionamento múltiplo (vários painéis numa pauta)
    public function inventarios(): BelongsToMany
    {
        return $this->belongsToMany(Inventario::class, 'inventario_pauta');
    }
}