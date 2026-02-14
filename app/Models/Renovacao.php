<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;  // ⬅️ ADICIONAR ESTA LINHA
use Spatie\Activitylog\LogOptions;           // ⬅️ ADICIONAR ESTA LINHA

class Renovacao extends Model
{
    use LogsActivity;  // ⬅️ ADICIONAR ESTA LINHA

    protected $fillable = [
        'ativo',
        'cliente',
        'comercial',
        'canal_selecionado',
        'endereco_manual',
        'data_inicio',
        'data_fim',
        'dia_padrao_captacao',
        'dia_padrao_entrega',
        'obs_midia',
        'obs_captacao',
        'origem',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'dia_padrao_captacao' => 'integer',
        'dia_padrao_entrega' => 'integer',
    ];

    // ⬅️ ADICIONAR ESTE MÉTODO COMPLETO
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('renovacao')
            ->setDescriptionForEvent(fn(string $eventName) => "Renovação {$eventName}");
    }
}