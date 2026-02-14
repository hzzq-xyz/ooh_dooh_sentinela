<?php

namespace App\Filament\Resources\Pautas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PautasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('data_insercao')
                    ->date()
                    ->sortable(),
                TextColumn::make('pi')
                    ->searchable(),
                TextColumn::make('cliente')
                    ->searchable(),
                TextColumn::make('origem')
                    ->searchable(),
                TextColumn::make('canal_selecionado')
                    ->searchable(),
                TextColumn::make('inventario.id')
                    ->searchable(),
                TextColumn::make('comercial')
                    ->searchable(),
                TextColumn::make('prazo_captacao')
                    ->date()
                    ->sortable(),
                TextColumn::make('prazo_envio')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_captacao')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_envio_real')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('motivo_atraso')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
