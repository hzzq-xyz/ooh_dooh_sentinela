<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validacao extends Model
{
    // Define o nome da tabela caso o Laravel não identifique automaticamente
    protected $table = 'validacoes';

    // Permite a gravação em massa (Mass Assignment)
    protected $guarded = [];

    /**
     * Relacionamento: Uma validação pertence a um item do inventário.
     */
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }
}