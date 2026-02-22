<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VeiculacaoResource\Pages;
use App\Models\Veiculacao;
use App\Models\Inventario;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use UnitEnum;
use BackedEnum;
use Filament\Actions\BulkAction;
use App\Mail\InformativoVeiculacao;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class VeiculacaoResource extends Resource
{
    protected static ?string $model = Veiculacao::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tv';
    
    protected static ?string $label = 'Veiculação';
    
    protected static ?string $pluralLabel = 'Veiculações';
    
    protected static string|UnitEnum|null $navigationGroup = 'Mídia e Planejamento';

    public static function form(Schema $schema): Schema
    {
        $inventarios = Inventario::all()->filter(function ($item) {
            return $item->codigo !== null && $item->codigo !== '';
        })->pluck('codigo', 'id')->toArray();
        
        return $schema->schema([
            Section::make('Dados da Veiculação')->schema([
                Select::make('inventario_id')
                    ->label('Painel')
                    ->options($inventarios)
                    ->searchable()
                    ->required(),
                
                TextInput::make('cliente')
                    ->label('Cliente')
                    ->required()
                    ->maxLength(255),
                
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->required(),
                
                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->required()
                    ->after('data_inicio'),
                
                TextInput::make('slots')
                    ->label('Quantidade de Slots')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),
                
                Select::make('tipo_acordo')
                    ->label('Tipo de Acordo')
                    ->options([
                        'PAGO' => 'Pago',
                        'CORTESIA' => 'Cortesia',
                        'PERMUTA' => 'Permuta',
                        'INTERNO' => 'Interno',
                    ])
                    ->default('PAGO')
                    ->required(),
                
                Textarea::make('observacoes')
                    ->label('Observações')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_inicio', 'desc')
            ->columns([
                TextColumn::make('inventario.codigo')
                    ->label('Painel')
                    ->searchable()
                    ->sortable()
                    ->default('S/N'),
                
                TextColumn::make('inventario.canal')
                    ->label('Canal')
                    ->searchable()
                    ->sortable()
                    ->default('Sem canal'),
                
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                
                TextColumn::make('data_fim')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                
                TextColumn::make('slots')
                    ->label('Slots')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('tipo_acordo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAGO' => 'success',
                        'CORTESIA' => 'info',
                        'PERMUTA' => 'warning',
                        'INTERNO' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_acordo')
                    ->label('Tipo de Acordo')
                    ->options([
                        'PAGO' => 'Pago',
                        'CORTESIA' => 'Cortesia',
                        'PERMUTA' => 'Permuta',
                        'INTERNO' => 'Interno',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
           ->bulkActions([
    DeleteBulkAction::make(),
    
    // 👇 ADICIONE TUDO ISSO AQUI
    BulkAction::make('enviarInformativo')
        ->label('Enviar Informativo Agrupado')
        ->icon('heroicon-o-envelope')
        ->color('success')
        ->form([
            TextInput::make('email_destinatario')
                ->label('E-mail do Cliente')
                ->email()
                ->required()
                ->placeholder('cliente@example.com'),
            
            TextInput::make('email_copia')
                ->label('Enviar Cópia (opcional)')
                ->email(),
            
            TextInput::make('nome_cliente')
                ->label('Nome do Cliente')
                ->required(),
            
            TextInput::make('nome_campanha')
                ->label('Nome da Campanha')
                ->default('INSTITUCIONAL')
                ->required(),
            
            TextInput::make('atendimento')
                ->label('Atendimento/Comercial')
                ->placeholder('Ex: NOME DO EXECUTIVO')
                ->required(),
        ])
        ->action(function (array $data, $records) {
            try {
                $dataInicio = $records->min('data_inicio');
                $dataFim = $records->max('data_fim');
                
                $imagemCampanha = null;
                foreach ($records as $v) {
                    if (isset($v->imagem_campanha) && $v->imagem_campanha) {
                        $imagemCampanha = Storage::url($v->imagem_campanha);
                        break;
                    }
                }
                
                $veiculacoes = $records->load('inventario');
                
                Mail::to($data['email_destinatario'])->send(
                    new InformativoVeiculacao(
                        veiculacoes: $veiculacoes,
                        nomeCliente: $data['nome_cliente'],
                        nomeCampanha: $data['nome_campanha'],
                        atendimento: $data['atendimento'],
                        dataInicio: $dataInicio,
                        dataFim: $dataFim,
                        imagemCampanha: $imagemCampanha
                    )
                );
                
                if (!empty($data['email_copia'])) {
                    Mail::to($data['email_copia'])->send(
                        new InformativoVeiculacao(
                            veiculacoes: $veiculacoes,
                            nomeCliente: $data['nome_cliente'],
                            nomeCampanha: $data['nome_campanha'],
                            atendimento: $data['atendimento'],
                            dataInicio: $dataInicio,
                            dataFim: $dataFim,
                            imagemCampanha: $imagemCampanha
                        )
                    );
                }
                
                \Filament\Notifications\Notification::make()
                    ->title('Informativo enviado com sucesso!')
                    ->body(sprintf(
                        'Email enviado para %s com %d %s',
                        $data['email_destinatario'],
                        $records->count(),
                        $records->count() > 1 ? 'painéis' : 'painel'
                    ))
                    ->success()
                    ->send();
                    
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Erro ao enviar informativo')
                    ->body('Erro: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        })
        ->deselectRecordsAfterCompletion()
        ->requiresConfirmation()
        ->modalHeading('Enviar Informativo de Veiculação')
        ->modalDescription('Selecione os dados para enviar um informativo único com todas as plataformas.')
        ->modalSubmitActionLabel('Enviar E-mail')
        ->modalWidth('xl'),
]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVeiculacaos::route('/'),
            'create' => Pages\CreateVeiculacao::route('/create'),
            'edit' => Pages\EditVeiculacao::route('/{record}/edit'),
        ];
    }
}