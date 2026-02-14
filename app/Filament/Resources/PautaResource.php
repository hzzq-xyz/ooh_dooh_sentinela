<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PautaResource\Pages;
use App\Models\Pauta;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema; 
use Filament\Schemas\Components\Utilities\Get; 
use Filament\Schemas\Components\Utilities\Set; 
use Filament\Notifications\Notification;
use Filament\Actions\Action; 
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PautaResource extends Resource
{
    protected static ?string $model = Pauta::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $label = 'Pauta';
    protected static string|\UnitEnum|null $navigationGroup = 'Operacional';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\DatePicker::make('data_insercao')
                    ->label('📅 Data Inserção')
                    ->required()
                    ->default(now())
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if (!$state) return;
                        $date = Carbon::parse($state);
                        $set('prazo_captacao', $date->copy()->addDays(2)->format('Y-m-d'));
                        $set('prazo_envio', $date->copy()->addDays(5)->format('Y-m-d'));
                    }),

                Forms\Components\TextInput::make('pi')->label('Nº PI'),
                Forms\Components\TextInput::make('cliente')->required()->label('🏢 Cliente'),
                
                Forms\Components\Select::make('origem')
                    ->label('👤 Equipe')
                    ->options(['TI' => 'TI', 'FOTÓGRAFO' => 'FOTÓGRAFO', 'TERCEIROS' => 'TERCEIROS'])
                    ->default('FOTÓGRAFO')
                    ->required(),

                Forms\Components\Select::make('canal_selecionado')
                    ->label('🔍 Filtrar Canal')
                    ->options(fn () => Inventario::whereNotNull('canal')
                        ->where('canal', '!=', '')
                        ->distinct()
                        ->pluck('canal', 'canal')
                        ->toArray())
                    ->live()
                    ->searchable()
                    ->columnSpan(2),
Forms\Components\Select::make('inventarios')
    ->label('📍 Painéis')
    ->multiple()
    ->relationship('inventarios', 'codigo', function (Builder $query, Get $get) {
        $canal = $get('canal_selecionado');
        return $query
            ->whereNotNull('codigo') // <--- GARANTE QUE NÃO VEM NADA NULO PARA O LABEL
            ->where('codigo', '!=', '')
            ->when($canal, fn ($q) => $q->where('canal', $canal));
    })
    ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->codigo ?? $record->endereco ?? "ID: {$record->id}"))
    ->preload()
    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                         if ($state) {
                            $enderecos = Inventario::whereIn('id', $state)->pluck('endereco')->filter()->implode("\n");
                            $set('endereco_manual', $enderecos);
                         }
                    }),

                Forms\Components\Textarea::make('endereco_manual')
                    ->label('📝 Endereços')
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('obs_midia')->label('Obs Mídia')->rows(2),
                Forms\Components\Textarea::make('obs_captacao')->label('Obs Captação')->rows(2)->columnSpan(2),

                Forms\Components\DatePicker::make('prazo_captacao')->label('⚠️ Captação'),
                Forms\Components\DatePicker::make('prazo_envio')->label('🚀 Entrega'),
                
                Forms\Components\Select::make('status')
                    ->options(['CAPTAÇÃO' => 'CAPTAÇÃO', 'MONTAGEM' => 'MONTAGEM', 'ENVIADO' => 'ENVIADO'])
                    ->default('CAPTAÇÃO')
                    ->required(),

                Forms\Components\TextInput::make('link_drive')->label('🔗 Drive')->url()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->orderByRaw("CASE WHEN cliente LIKE '%CONCORR%' THEN 0 ELSE 1 END")
                             ->orderBy('data_insercao', 'desc');
            })
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('data_insercao')->label('Ins.')->date('d/m/y')->sortable(),
                Tables\Columns\TextColumn::make('pi')->label('PI')->searchable()->toggleable(),
                
                Tables\Columns\TextColumn::make('origem')->label('Equipe')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'TI' => 'info', 'FOTÓGRAFO' => 'warning', 'TERCEIROS' => 'purple', default => 'gray',
                    })->sortable(),

                Tables\Columns\TextColumn::make('cliente')->label('Cliente')->searchable()->weight('bold')->limit(25)
                    ->tooltip(fn ($record) => $record->cliente),

                // --- COLUNA CANAL CORRIGIDA (Híbrida: Importação + Manual) ---
                Tables\Columns\TextColumn::make('canal_exibicao')
                    ->label('Canal')
                    ->state(function (Pauta $record) {
                        // 1. Tenta pegar de múltiplos painéis (Edição Manual)
                        $canais = $record->inventarios->pluck('canal')->filter()->unique();
                        if ($canais->isNotEmpty()) return $canais->implode(', ');

                        // 2. Tenta pegar do vínculo simples (Importação CSV)
                        if ($record->inventario) return $record->inventario->canal;

                        // 3. Fallback
                        return $record->canal_selecionado ?? '-';
                    })
                    ->badge()
                    ->color('gray')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('endereco_manual')->label('Endereços')->wrap()->limit(50)
                    ->tooltip(fn ($record) => $record->endereco_manual)->searchable()->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('prazo_captacao')->label('Pz. Cap.')->date('d/m/y')->color('warning')->sortable(),
                Tables\Columns\TextColumn::make('prazo_envio')->label('Pz. Env.')->date('d/m/y')->color('danger')->sortable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CAPTAÇÃO' => 'danger', 'MONTAGEM' => 'warning', 'ENVIADO' => 'success', default => 'gray',
                    })->sortable(),

                Tables\Columns\IconColumn::make('link_drive')->label('Drive')->icon('heroicon-o-link')->url(fn ($state) => $state, true)->color('info')->toggleable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('origem')->label('Filtrar Equipe')->options(['TI' => 'TI', 'FOTÓGRAFO' => 'FOTÓGRAFO', 'TERCEIROS' => 'TERCEIROS']),
                Tables\Filters\SelectFilter::make('status')->label('Filtrar Status')->options(['CAPTAÇÃO' => 'CAPTAÇÃO', 'MONTAGEM' => 'MONTAGEM', 'ENVIADO' => 'ENVIADO']),
                Tables\Filters\Filter::make('prazo_captacao')->label('📅 Prazo Captação')
                    ->form([
                        Forms\Components\DatePicker::make('de')->label('De'),
                        Forms\Components\DatePicker::make('ate')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['de'], fn ($q) => $q->whereDate('prazo_captacao', '>=', $data['de']))
                        ->when($data['ate'], fn ($q) => $q->whereDate('prazo_captacao', '<=', $data['ate']))
                    ),
                Tables\Filters\Filter::make('data_insercao')->label('📥 Data Inserção')
                    ->form([
                        Forms\Components\DatePicker::make('de')->label('De'),
                        Forms\Components\DatePicker::make('ate')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['de'], fn ($q) => $q->whereDate('data_insercao', '>=', $data['de']))
                        ->when($data['ate'], fn ($q) => $q->whereDate('data_insercao', '<=', $data['ate']))
                    )
            ])
            ->actions([
                Action::make('edit')->label('')->icon('heroicon-o-pencil-square')->url(fn (Pauta $record) => Pages\EditPauta::getUrl([$record])),
                
                Action::make('maps')
                    ->label('Maps')
                    ->icon('heroicon-o-map-pin')
                    ->color('success')
                    ->modalHeading('GPS / Localização')
                    ->modalContent(fn (Pauta $record) => new HtmlString(static::renderRoteiroLinks($record))),

                Action::make('delete')->label('')->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()->action(fn (Pauta $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('gerar_pdf')->label('📄 Gerar PDF (Seleção)')->icon('heroicon-o-printer')
                        ->action(function (Collection $records) {
                            $pdf = Pdf::loadView('pdf.roteiro-fotografo', ['records' => $records->sortBy('prazo_captacao')]);
                            return response()->streamDownload(fn () => print($pdf->output()), 'roteiro-fotografia.pdf');
                        }),
                ]),
            ]);
    }

    private static function renderRoteiroLinks(Pauta $record): string 
    {
        $html = '<div class="space-y-4">';
        
        // Lógica Híbrida também para o GPS (Importação + Manual)
        $items = $record->inventarios;
        
        // Se a lista múltipla estiver vazia, tenta pegar o item singular da importação
        if ($items->isEmpty() && $record->inventario) {
            $items = collect([$record->inventario]);
        }

        if ($items->isEmpty() && $record->endereco_manual) {
             $link = "https://www.google.com/maps/search/?api=1&query=" . urlencode($record->endereco_manual . ", Porto Alegre - RS");
             $html .= "<div class='p-2 bg-gray-50 rounded border'><p>{$record->endereco_manual}</p><a href='{$link}' target='_blank' class='text-blue-600 font-bold'>📍 Abrir no Maps</a></div>";
        }

        foreach ($items as $inv) {
            $endereco = $inv->endereco ?? "Painel {$inv->codigo}";
            $link = (!empty($inv->latitude)) ? "https://www.google.com/maps/search/?api=1&query={$inv->latitude},{$inv->longitude}" : "https://www.google.com/maps/search/?api=1&query=" . urlencode($endereco . ", Porto Alegre - RS");
            $html .= "<div class='p-2 bg-gray-50 rounded border flex justify-between items-center'><span class='font-bold'>" . ($inv->codigo ?? 'Sem Código') . "</span><a href='{$link}' target='_blank' class='text-green-600 font-bold'>GPS 🚀</a></div>";
        }
        return $html . '</div>';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPautas::route('/'),
            'create' => Pages\CreatePauta::route('/create'),
            'edit' => Pages\EditPauta::route('/{record}/edit'),
        ];
    }
}