<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RenovacaoResource\Pages;
use App\Models\Renovacao;
use App\Models\Pauta; 
use App\Models\Inventario;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class RenovacaoResource extends Resource
{
    protected static ?string $model = Renovacao::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    
    protected static ?string $label = 'Renovação Mensal';
    
    protected static ?string $pluralLabel = 'Renovações Mensais';
    
    protected static string|UnitEnum|null $navigationGroup = 'Gestão';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Dados do Contrato')->schema([
                Toggle::make('ativo')->default(true)->columnSpanFull(),
                
                TextInput::make('cliente')->required(),
                Select::make('canal_selecionado')
                    ->label('Canal')
                    ->options(fn () => Inventario::pluck('canal', 'canal')->unique()->toArray())
                    ->searchable(),

                DatePicker::make('data_inicio')
                    ->label('Início do Contrato'),
                DatePicker::make('data_fim')
                    ->label('Fim do Contrato')
                    ->helperText('Deixe em branco se for indeterminado'),

                TextInput::make('comercial')->required(),
                Textarea::make('endereco_manual')->label('Endereços')->columnSpanFull(),
            ])->columns(2),

            Section::make('Regra de Datas (Dia do Mês)')->schema([
                TextInput::make('dia_padrao_captacao')
                    ->label('Dia p/ Captação')
                    ->numeric()
                    ->default(15)
                    ->suffix('de cada mês')
                    ->required(),
                TextInput::make('dia_padrao_entrega')
                    ->label('Dia p/ Entrega')
                    ->numeric()
                    ->default(20)
                    ->suffix('de cada mês')
                    ->required(),
            ])->columns(2),

            Section::make('Observações Padrão')->schema([
                Select::make('origem')
                    ->options(['TI' => 'TI', 'FOTÓGRAFO' => 'FOTÓGRAFO', 'TERCEIROS' => 'TERCEIROS'])
                    ->default('FOTÓGRAFO')
                    ->required(),
                Textarea::make('obs_midia')->label('Obs Mídia'),
                Textarea::make('obs_captacao')->label('Obs Captação'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                Action::make('gerar_todos_ativos')
                    ->label('Gerar Pautas (Todos Ativos)')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Gerar Pautas em Massa?')
                    ->modalDescription('Isso irá processar TODOS os contratos marcados como ATIVOS no sistema, respeitando as datas de validade.')
                    ->form([
                        DatePicker::make('mes_referencia')
                            ->label('Mês de Referência')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $ativos = Renovacao::where('ativo', true)->get();
                        
                        $count = 0;
                        $ignored = 0;
                        $ref = Carbon::parse($data['mes_referencia']);

                        foreach ($ativos as $record) {
                            if ($record->data_fim && $ref->gt(Carbon::parse($record->data_fim))) {
                                $ignored++; continue;
                            }
                            if ($record->data_inicio && $ref->lt(Carbon::parse($record->data_inicio)->startOfMonth())) {
                                $ignored++; continue;
                            }

                            self::criarPauta($record, $ref);
                            $count++;
                        }

                        $msg = "$count pautas geradas com sucesso!";
                        if($ignored > 0) $msg .= " ($ignored ignoradas por validade)";

                        // 🔥 LOG: Registrar ação em massa
                        activity()
                            ->withProperties([
                                'mes_referencia' => $ref->format('Y-m'),
                                'total_geradas' => $count,
                                'total_ignoradas' => $ignored,
                                'total_contratos_ativos' => $ativos->count(),
                            ])
                            ->log('Geração em massa de pautas');

                        Notification::make()->title($msg)->success()->send();
                    }),
            ])
            ->striped()
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\IconColumn::make('ativo')->label('Atv.')->boolean(),
                Tables\Columns\TextColumn::make('cliente')
                    ->searchable()
                    ->weight('bold')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->cliente),
                
                Tables\Columns\TextColumn::make('canal_selecionado')
                    ->label('Canal')
                    ->badge()
                    ->color('gray')
                    ->limit(15),
                
                Tables\Columns\TextColumn::make('obs_midia')
                    ->label('Obs. Mídia')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->obs_midia)
                    ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                
                Tables\Columns\TextColumn::make('dia_padrao_captacao')
                    ->label('Regra (Dia Cap / Env)')
                    ->formatStateUsing(fn ($record) => $record->dia_padrao_captacao . ' / ' . $record->dia_padrao_entrega)
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Vencimento')
                    ->date('d/m/y')
                    ->placeholder('Indet.')
                    ->sortable()
                    ->color(fn ($state) => $state && Carbon::parse($state)->lt(now()) ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ativo')->label('Apenas Ativos'),
                Tables\Filters\Filter::make('vencidos')->query(fn ($query) => $query->where('data_fim', '<', now())),
            ])
            ->recordActions([
                EditAction::make()->label(''),
                
                Action::make('gerar_pauta')
                    ->label('Gerar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->button()
                    ->form([
                        DatePicker::make('mes_referencia')
                            ->label('Mês de Referência')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (Renovacao $record, array $data) {
                        $ref = Carbon::parse($data['mes_referencia']);
                        if ($record->data_fim && $ref->gt(Carbon::parse($record->data_fim))) {
                            Notification::make()->title('Erro: Contrato Encerrado!')->body("Venceu em " . Carbon::parse($record->data_fim)->format('d/m/Y'))->danger()->send();
                            return;
                        }
                        if ($record->data_inicio && $ref->lt(Carbon::parse($record->data_inicio)->startOfMonth())) {
                            Notification::make()->title('Erro: Contrato não iniciou!')->body("Inicia em " . Carbon::parse($record->data_inicio)->format('d/m/Y'))->danger()->send();
                            return;
                        }
                        
                        self::criarPauta($record, $ref);
                        
                        // 🔥 LOG: Registrar geração de pauta individual
                        activity()
                            ->performedOn($record)
                            ->withProperties([
                                'mes_referencia' => $ref->format('Y-m'),
                                'prazo_captacao' => $ref->copy()->day($record->dia_padrao_captacao)->format('Y-m-d'),
                                'prazo_envio' => $ref->copy()->day($record->dia_padrao_entrega)->format('Y-m-d'),
                            ])
                            ->log('Gerou pauta mensal manualmente');
                        
                        Notification::make()->title('Pauta gerada!')->success()->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('gerar_lote')
                    ->label('Gerar Pautas Selecionadas')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $count = 0; $ignored = 0; $ref = now(); 
                        foreach ($records as $record) {
                            if(!$record->ativo) continue;
                            if ($record->data_fim && $ref->gt(Carbon::parse($record->data_fim))) { $ignored++; continue; }
                            if ($record->data_inicio && $ref->lt(Carbon::parse($record->data_inicio)->startOfMonth())) { $ignored++; continue; }
                            self::criarPauta($record, $ref);
                            $count++;
                        }
                        
                        $msg = "$count pautas geradas!";
                        if($ignored > 0) $msg .= " ($ignored ignoradas por validade)";
                        
                        // 🔥 LOG: Registrar geração em lote
                        activity()
                            ->withProperties([
                                'mes_referencia' => $ref->format('Y-m'),
                                'total_selecionadas' => $records->count(),
                                'total_geradas' => $count,
                                'total_ignoradas' => $ignored,
                            ])
                            ->log('Geração em lote de pautas (seleção manual)');
                        
                        Notification::make()->title($msg)->success()->send();
                    }),
            ]);
    }

    protected static function criarPauta($record, $ref)
    {
        $diaCap = (int) $record->dia_padrao_captacao;
        $diaEnv = (int) $record->dia_padrao_entrega;
        $prazoCaptacao = $ref->copy()->day($diaCap);
        $prazoEnvio    = $ref->copy()->day($diaEnv);
        
        Pauta::create([
            'data_insercao'     => now(),
            'cliente'           => $record->cliente,
            'canal_selecionado' => $record->canal_selecionado,
            'endereco_manual'   => $record->endereco_manual,
            'comercial'         => $record->comercial,
            'obs_midia'         => $record->obs_midia,
            'obs_captacao'      => $record->obs_captacao,
            'origem'            => $record->origem,
            'status'            => 'CAPTAÇÃO',
            'prazo_captacao'    => $prazoCaptacao->format('Y-m-d'),
            'prazo_envio'       => $prazoEnvio->format('Y-m-d'),
        ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRenovacaos::route('/'),
            'create' => Pages\CreateRenovacao::route('/create'),
            'edit' => Pages\EditRenovacao::route('/{record}/edit'),
        ];
    }
}