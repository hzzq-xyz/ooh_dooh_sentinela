<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Configuração do log de atividades
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email']) // Campos monitorados
            ->logOnlyDirty() // Apenas mudanças reais
            ->dontSubmitEmptyLogs() // Sem logs vazios
            ->useLogName('user') // Nome do log
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Usuário criado',
                'updated' => 'Usuário atualizado', 
                'deleted' => 'Usuário excluído',
                default => "Usuário {$eventName}"
            });
    }
}