<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;
use UnitEnum;

class DeployControl extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $title = 'Controle de Deploy';
    protected static string | UnitEnum | null $navigationGroup = 'Desenvolvimento';
    protected static ?string $navigationLabel = 'Atualizar Git';

    protected string $view = 'filament.pages.deploy-control';

    public static function shouldRegisterNavigation(): bool
    {
        return true; 
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('executar_deploy')
                ->label('Sincronizar Código (Git Pull)')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Sincronizar com GitHub?')
                ->modalDescription('Isso executará o git reset --hard para garantir que o servidor fique idêntico ao seu computador.')
                ->action(function () {
                    // Verifica se a função está liberada na Hostinger
                    if (!function_exists('shell_exec')) {
                        Notification::make()
                            ->title('Erro de Configuração')
                            ->body('A função shell_exec está desativada no seu PHP.ini da Hostinger.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Executa o script que você acabou de limpar com o sed
                    $scriptPath = base_path('deploy.sh');
                    $output = \shell_exec("sh $scriptPath 2>&1");

                    // Retorna o log para você no painel
                    Notification::make()
                        ->title('Resultado da Sincronização')
                        ->body(nl2br($output)) 
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}