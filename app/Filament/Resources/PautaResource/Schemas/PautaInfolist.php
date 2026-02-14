<?php

namespace App\Filament\Resources\Pautas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PautaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('data_insercao')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('pi')
                    ->placeholder('-'),
                TextEntry::make('cliente')
                    ->placeholder('-'),
                TextEntry::make('origem'),
                TextEntry::make('canal_selecionado')
                    ->placeholder('-'),
                TextEntry::make('inventario.id')
                    ->label('Inventario')
                    ->placeholder('-'),
                TextEntry::make('endereco_manual')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('comercial')
                    ->placeholder('-'),
                TextEntry::make('obs_midia')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('prazo_captacao')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('prazo_envio')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('data_captacao')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('data_envio_real')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('obs_captacao')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('link_drive')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('motivo_atraso')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
