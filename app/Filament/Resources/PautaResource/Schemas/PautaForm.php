<?php

namespace App\Filament\Resources\Pautas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PautaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_insercao'),
                TextInput::make('pi')
                    ->default(null),
                TextInput::make('cliente')
                    ->default(null),
                TextInput::make('origem')
                    ->required()
                    ->default('FOTÓGRAFO'),
                TextInput::make('canal_selecionado')
                    ->default(null),
                Select::make('inventario_id')
                    ->relationship('inventario', 'id')
                    ->default(null),
                Textarea::make('endereco_manual')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('comercial')
                    ->default(null),
                Textarea::make('obs_midia')
                    ->default(null)
                    ->columnSpanFull(),
                DatePicker::make('prazo_captacao'),
                DatePicker::make('prazo_envio'),
                DatePicker::make('data_captacao'),
                DatePicker::make('data_envio_real'),
                TextInput::make('status')
                    ->required()
                    ->default('CAPTAÇÃO'),
                Textarea::make('obs_captacao')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('link_drive')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('motivo_atraso')
                    ->default(null),
            ]);
    }
}
