<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Veiculacao extends Model
{
    protected $table = 'veiculacoes';
    
    protected $fillable = [
    'inventario_id',
    'cliente',
    'email_cliente',      // 👈 NOVO
    'atendimento',        // 👈 NOVO
    'data_inicio',
    'data_fim',
    'slots',
    'tipo_acordo',
    'observacoes',
    'imagem_campanha',    // 👈 NOVO
];
    
    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'slots' => 'integer',
    ];
    
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }
}