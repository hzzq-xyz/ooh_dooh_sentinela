<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use UnitEnum;
use BackedEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $label = 'Log de Atividade';
    
    protected static ?string $pluralLabel = 'Logs de Atividades';
    
    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detalhes da Atividade')->schema([
                Grid::make(2)->schema([
                    DateTimePicker::make('created_at')
                        ->label('Data/Hora')
                        ->disabled(),
                    
                    TextInput::make('causer.name')
                        ->label('Usuário')
                        ->default('Sistema')
                        ->disabled(),
                ]),
                
                Grid::make(2)->schema([
                    TextInput::make('log_name')
                        ->label('Tipo de Log')
                        ->disabled(),
                    
                    TextInput::make('description')
                        ->label('Ação Executada')
                        ->formatStateUsing(function ($state) {
                            return str_replace(
                                ['created', 'updated', 'deleted'],
                                ['criou', 'atualizou', 'deletou'],
                                $state
                            );
                        })
                        ->disabled(),
                ]),
                
                Grid::make(3)->schema([
                    TextInput::make('subject_type')
                        ->label('Tipo do Registro')
                        ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                        ->disabled(),
                    
                    TextInput::make('subject_id')
                        ->label('ID do Registro')
                        ->default('-')
                        ->disabled(),
                    
                    TextInput::make('event')
                        ->label('Evento')
                        ->default('-')
                        ->disabled(),
                ]),
            ]),
            
            Section::make('Valores Antigos')->schema([
                Textarea::make('old_values')
                    ->label('Antes da Alteração')
                    ->formatStateUsing(function ($record) {
                        if (!$record || !is_array($record->properties)) {
                            return 'Nenhum valor anterior';
                        }
                        
                        $old = $record->properties['old'] ?? [];
                        
                        if (empty($old)) {
                            return 'Nenhum valor anterior (registro novo)';
                        }
                        
                        $text = '';
                        foreach ($old as $key => $value) {
                            $displayValue = is_bool($value) ? ($value ? 'sim' : 'não') : $value;
                            $text .= "{$key}: {$displayValue}\n";
                        }
                        
                        return $text ?: 'Nenhum valor anterior';
                    })
                    ->rows(10)
                    ->disabled()
                    ->columnSpanFull(),
            ])->visible(fn ($record) => !empty($record->properties['old'] ?? [])),
            
            Section::make('Valores Novos')->schema([
                Textarea::make('new_values')
                    ->label('Depois da Alteração')
                    ->formatStateUsing(function ($record) {
                        if (!$record || !is_array($record->properties)) {
                            return 'Sem dados';
                        }
                        
                        $attributes = $record->properties['attributes'] ?? [];
                        
                        if (empty($attributes)) {
                            return 'Sem dados';
                        }
                        
                        $text = '';
                        foreach ($attributes as $key => $value) {
                            $displayValue = is_bool($value) ? ($value ? 'sim' : 'não') : $value;
                            $text .= "{$key}: {$displayValue}\n";
                        }
                        
                        return $text ?: 'Sem dados';
                    })
                    ->rows(10)
                    ->disabled()
                    ->columnSpanFull(),
            ])->visible(fn ($record) => !empty($record->properties['attributes'] ?? [])),
            
            Section::make('Dados Adicionais')->schema([
                Textarea::make('custom_properties')
                    ->label('Propriedades Customizadas')
                    ->formatStateUsing(function ($record) {
                        if (!$record || !is_array($record->properties)) {
                            return 'Nenhuma propriedade adicional';
                        }
                        
                        $filtered = array_diff_key($record->properties, array_flip(['old', 'attributes']));
                        
                        if (empty($filtered)) {
                            return 'Nenhuma propriedade adicional';
                        }
                        
                        return json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    })
                    ->rows(6)
                    ->disabled()
                    ->columnSpanFull(),
            ])->visible(function ($record) {
                if (!$record || !is_array($record->properties)) {
                    return false;
                }
                $filtered = array_diff_key($record->properties, array_flip(['old', 'attributes']));
                return !empty($filtered);
            }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable()
                    ->size('sm'),
                
                TextColumn::make('log_name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'renovacao' => 'success',
                        'pauta' => 'info',
                        'user' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Ação')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return str_replace(
                            ['created', 'updated', 'deleted'],
                            ['criou', 'atualizou', 'deletou'],
                            $state
                        );
                    })
                    ->weight('medium')
                    ->size('sm'),
                
                TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->badge()
                    ->color('gray')
                    ->size('sm'),
                
                TextColumn::make('subject_id')
                    ->label('ID')
                    ->size('sm')
                    ->alignCenter(),
                
                TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->searchable()
                    ->default('Sistema')
                    ->formatStateUsing(fn ($record) => $record->causer?->name ?? 'Sistema')
                    ->badge()
                    ->color('primary')
                    ->size('sm'),
                
                TextColumn::make('mudancas')
                    ->label('Campos Alterados')
                    ->formatStateUsing(function ($record) {
                        $properties = $record->properties;
                        
                        if (!is_array($properties)) {
                            return '-';
                        }
                        
                        $attributes = $properties['attributes'] ?? [];
                        $old = $properties['old'] ?? [];
                        
                        if (empty($attributes) && empty($old)) {
                            $mainFields = array_filter($properties, fn($key) => !in_array($key, ['attributes', 'old']), ARRAY_FILTER_USE_KEY);
                            if (empty($mainFields)) {
                                return '-';
                            }
                            return implode(', ', array_keys($mainFields));
                        }
                        
                        $changes = [];
                        foreach ($attributes as $key => $value) {
                            if (isset($old[$key]) && $old[$key] != $value) {
                                $changes[] = $key;
                            } elseif (!isset($old[$key])) {
                                $changes[] = $key;
                            }
                        }
                        
                        if (empty($changes)) {
                            return '-';
                        }
                        
                        return implode(', ', array_slice($changes, 0, 5)) . (count($changes) > 5 ? '...' : '');
                    })
                    ->limit(60)
                    ->size('sm')
                    ->color('gray')
                    ->tooltip(function ($record) {
                        $properties = $record->properties;
                        
                        if (!is_array($properties)) {
                            return null;
                        }
                        
                        $attributes = $properties['attributes'] ?? [];
                        $old = $properties['old'] ?? [];
                        
                        if (empty($attributes) && empty($old)) {
                            return null;
                        }
                        
                        $text = '';
                        foreach ($attributes as $key => $value) {
                            if (isset($old[$key]) && $old[$key] != $value) {
                                $oldVal = is_bool($old[$key]) ? ($old[$key] ? 'sim' : 'não') : $old[$key];
                                $newVal = is_bool($value) ? ($value ? 'sim' : 'não') : $value;
                                $text .= "{$key}: {$oldVal} → {$newVal}\n";
                            } elseif (!isset($old[$key])) {
                                $newVal = is_bool($value) ? ($value ? 'sim' : 'não') : $value;
                                $text .= "{$key}: {$newVal}\n";
                            }
                        }
                        
                        return $text ?: null;
                    }),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Tipo de Log')
                    ->options([
                        'renovacao' => 'Renovação',
                        'pauta' => 'Pauta',
                        'user' => 'Usuário',
                        'default' => 'Padrão',
                    ]),
                
                SelectFilter::make('causer_id')
                    ->label('Usuário')
                    ->relationship('causer', 'name')
                    ->searchable()
                    ->preload(),
                
                Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from')
                            ->label('De'),
                        DateTimePicker::make('created_until')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Filter::make('hoje')
                    ->label('Hoje')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                
                Filter::make('esta_semana')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->recordActions([
                ViewAction::make()->label('Detalhes'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}