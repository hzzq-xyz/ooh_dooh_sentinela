<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PautaResource\Pages;
use App\Models\Pauta;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class PautaResource extends Resource
{
    protected static ?string $model = Pauta::class;
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $label = 'Minhas Tarefas';

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('origem', 'FOTÓGRAFO')->where('status', 'CAPTAÇÃO'))
            ->columns([
                Tables\Columns\TextColumn::make('cliente')->weight('bold')->size('lg'),
                Tables\Columns\TextColumn::make('endereco_manual')->icon('heroicon-o-map-pin'),
                Tables\Columns\TextColumn::make('prazo_captacao')->date('d/m')->color('warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('realizar')
                    ->label('REALIZAR ROTEIRO')
                    ->icon('heroicon-o-map')
                    ->color('primary')
                    ->button()
                    ->modalHeading('Roteiro de Trabalho')
                    ->form(function (Pauta $record) {
                        $listaHtml = '<ul class="space-y-3 mb-4">';
                        // Gera links do Google Maps
                        $paineis = $record->inventarios->isEmpty() ? [] : $record->inventarios;
                        foreach ($paineis as $inv) {
                            $link = "https://maps.google.com/?q={$inv->latitude},{$inv->longitude}";
                            $listaHtml .= "<li><a href='{$link}' target='_blank' style='color:blue'>📍 Ir para {$inv->codigo}</a></li>";
                        }
                        $listaHtml .= '</ul>';

                        return [
                            Forms\Components\Placeholder::make('lista')->content(new HtmlString($listaHtml)),
                            Forms\Components\TextInput::make('link_drive')->label('Link das Fotos')->required()->url(),
                        ];
                    })
                    ->action(function (Pauta $record, array $data) {
                        $record->update(['link_drive' => $data['link_drive'], 'status' => 'MONTAGEM', 'data_captacao' => now()]);
                        Notification::make()->title('Enviado!')->success()->send();
                    }),
            ]);
    }
    
    public static function getPages(): array
    {
        return ['index' => Pages\ListPautas::route('/')];
    }
}