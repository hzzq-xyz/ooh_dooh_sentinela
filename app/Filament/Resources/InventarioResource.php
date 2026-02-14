<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Inventario;

// IMPORTS HÍBRIDOS (CORRETOS)
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
// Inputs do Forms
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $label = 'Inventário';
    protected static ?string $pluralLabel = 'Inventário';
    protected static string | \UnitEnum | null $navigationGroup = 'Estoque';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Classificação e Localização')
                    ->schema([
                        Select::make('tipo')
                            ->label('Tipo de Mídia')
                            ->options(['On' => 'Digital (On)', 'Off' => 'Estática (Off)'])
                            ->default('On')
                            ->live()
                            ->required(),

                        TextInput::make('codigo')->label('Cód. Externo'),
                        TextInput::make('canal')->required()->label('Canal'),

                        TextInput::make('impactos')
                            ->label('Impactos')
                            ->numeric()
                            ->suffix('mil/mês')
                            ->helperText('Opcional: Alcance estimado'),

                        TextInput::make('qtd_slots')
                            ->label('Capacidade (Slots)')
                            ->numeric()
                            ->default(6)
                            ->required(),

                        TextInput::make('cidade')->label('Cidade')->default('Porto Alegre'),
                        TextInput::make('bairro')->label('Bairro'),
                        TextInput::make('endereco')->required()->columnSpanFull(),
                        TextInput::make('coordenadas_gps')->label('GPS (Lat, Long)'),
                        Toggle::make('iluminado')->label('Ponto Iluminado'),
                    ])->columns(3),

                Section::make('Especificações Técnicas')
                    ->schema([
                        // CORREÇÃO AQUI: Removido 'Get' antes de '$get' para evitar erro de classe
                        TextInput::make('largura_px')
                            ->numeric()
                            ->label('Largura (px)')
                            ->visible(fn ($get) => $get('tipo') === 'On'),

                        TextInput::make('altura_px')
                            ->numeric()
                            ->label('Altura (px)')
                            ->visible(fn ($get) => $get('tipo') === 'On'),

                        Select::make('sistema')
                            ->options(['Xibo' => 'Xibo', 'Invian' => 'Invian', 'SME' => 'SME'])
                            ->visible(fn ($get) => $get('tipo') === 'On'),

                        TextInput::make('tempo_maximo')
                            ->numeric()
                            ->label('Tempo (seg)')
                            ->visible(fn ($get) => $get('tipo') === 'On'),

                        FileUpload::make('mockup_image')
                            ->label('Imagem da Moldura (Mockup)')
                            ->image()
                            ->directory('mockups')
                            ->visibility('public')
                            ->visible(fn ($get) => $get('tipo') === 'On')
                            ->columnSpanFull(),

                        Textarea::make('mockup_css')
                            ->label('CSS de Ajuste (Posição)')
                            ->placeholder('Ex: top: 20px; left: 135px; width: 80%;')
                            ->rows(3)
                            ->helperText('Copie o CSS ajustado do validador antigo e cole aqui.')
                            ->visible(fn ($get) => $get('tipo') === 'On')
                            ->columnSpanFull(),

                        TextInput::make('dimensoes_fisicas')
                            ->label('Dimensões Físicas (Ex: 9x3m)')
                            ->visible(fn ($get) => $get('tipo') === 'Off'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'On' => 'success',
                        'Off' => 'warning',
                        default => 'secondary',
                    })
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Cód')
                    ->sortable()
                    ->searchable()
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\TextColumn::make('canal')
                    ->label('Canal')
                    ->searchable()
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\TextColumn::make('impactos')
                    ->label('Impactos')
                    ->suffix('k')
                    ->sortable()
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\TextColumn::make('bairro')
                    ->label('Bairro')
                    ->sortable()
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\TextColumn::make('endereco')
                    ->label('Endereço')
                    ->wrap()
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\TextColumn::make('especificacao')
                    ->label('Especificação')
                    ->getStateUsing(fn ($record) => $record->tipo === 'On'
                        ? "{$record->largura_px}x{$record->altura_px}"
                        : $record->dimensoes_fisicas)
                    ->extraAttributes(['class' => 'text-xs']),

                Tables\Columns\IconColumn::make('iluminado')
                    ->label('Ilum.')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sistema')
                    ->badge()
                    ->placeholder('---')
                    ->extraAttributes(['class' => 'text-xs']),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo de Mídia')
                    ->options(['On' => 'Digital', 'Off' => 'Estática']),

                SelectFilter::make('canal')
                    ->label('Canal')
                    ->options(fn () => Inventario::query()->pluck('canal', 'canal')->unique()->toArray())
                    ->searchable(),

                SelectFilter::make('bairro')
                    ->label('Bairro')
                    ->options(fn () => Inventario::query()->whereNotNull('bairro')->pluck('bairro', 'bairro')->unique()->toArray())
                    ->searchable(),

                // CORREÇÃO DO FILTRO TERNÁRIO (Argumentos nomeados, sem array)
                TernaryFilter::make('coordenadas_gps')
                    ->label('Possui GPS?')
                    ->placeholder('Todos os pontos')
                    ->trueLabel('Com GPS')
                    ->falseLabel('Sem GPS')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('coordenadas_gps')->where('coordenadas_gps', '!=', ''),
                        false: fn (Builder $query) => $query->whereNull('coordenadas_gps')->orWhere('coordenadas_gps', '=', '')
                    ),
            ])
            ->actions([
                Action::make('ver_mapa')
                    ->label('')
                    ->icon('heroicon-o-map-pin')
                    ->color('danger')
                    ->tooltip('Ver no Google Maps')
                    ->url(fn ($record) => $record->coordenadas_gps
                        ? "https://www.google.com/maps/search/?api=1&query={$record->coordenadas_gps}"
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->coordenadas_gps)),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventarios::route('/'),
            'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}