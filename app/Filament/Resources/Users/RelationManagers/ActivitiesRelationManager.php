<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    // Define o nome do relacionamento no Model User (padrão do pacote Spatie)
    protected static string $relationship = 'activities';

    protected static ?string $relatedResource = ActivityLogResource::class;

    protected static ?string $title = 'Histórico de Atividades';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                // Coluna 1: O que aconteceu (badge colorida)
                Tables\Columns\TextColumn::make('description')
                    ->label('Ação')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                // Coluna 2: Quem fez a alteração
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Realizado por')
                    ->placeholder('Sistema / Automático'),

                // Coluna 3: Quando aconteceu
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data e Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Nenhum filtro necessário por enquanto
            ])
            ->headerActions([
                // DEIXAR VAZIO: Removemos o CreateAction pois logs são automáticos
            ])
            ->actions([
                // Adiciona o botão de visualizar detalhes (tela a tela)
                Tables\Actions\ViewAction::make()->label('Detalhes'),
            ])
            ->defaultSort('created_at', 'desc'); // Ordena do mais recente para o mais antigo
    }
}