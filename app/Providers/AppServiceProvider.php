<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Tables\Table;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Configuração da Tabela (Listras ajudam na leitura)
        Table::configureUsing(function (Table $table): void {
            $table->striped();
        });

        // 2. CSS Global (Usando HTML puro para não dar erro de texto na tela)
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => Blade::render('
                <style>
                    /* Força as células da tabela a ficarem bem compactas */
                    .fi-ta-cell, .fi-ta-header-cell {
                        padding-top: 5px !important;
                        padding-bottom: 5px !important;
                    }
                    
                    /* Garante que o conteúdo fique centralizado */
                    .fi-ta-col-wrapper {
                        min-height: 20px !important;
                    }
                </style>
            ')
        );
    }
}