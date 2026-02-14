<?php

namespace App\Filament\Resources\PautaResource\Pages;

use App\Filament\Resources\PautaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Models\Pauta;
use App\Models\Inventario;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage; // IMPORTANTE: Necessário para achar o arquivo

class ListPautas extends ListRecords
{
    protected static string $resource = PautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // --- 1. BOTÃO SINCRONIZAR GOOGLE (MANTIDO) ---
            Actions\Action::make('sync_google')
                ->label('Sincronizar Google')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('url_csv')
                        ->label('Link CSV do Google Planilhas')
                        ->placeholder('Cole aqui o link do "Publicar na Web"')
                        ->required()
                        ->url(),
                ])
                ->action(function (array $data) {
                    try {
                        $response = Http::get($data['url_csv']);
                        if ($response->failed()) throw new \Exception('Erro ao acessar o link.');

                        $tempPath = storage_path('app/temp_import_google.csv');
                        file_put_contents($tempPath, $response->body());

                        $this->processarImportacao($tempPath);
                        @unlink($tempPath);

                    } catch (\Exception $e) {
                        Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                    }
                }),

            // --- 2. BOTÃO UPLOAD CSV (CORRIGIDO O ERRO DE CAMINHO) ---
            Actions\Action::make('importar_csv')
                ->label('Upload CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('arquivo_csv')
                        ->label('Arquivo CSV (Pauta)')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->disk('public') // <--- CORREÇÃO 1: Força salvar no disco público
                        ->directory('importacoes') // Organiza numa pasta
                        ->required(),
                ])
                ->action(function (array $data) {
                    // <--- CORREÇÃO 2: Usa o Storage para pegar o caminho real correto, onde quer que esteja
                    $caminhoReal = Storage::disk('public')->path($data['arquivo_csv']);
                    
                    $this->processarImportacao($caminhoReal);
                }),

            // --- 3. BOTÃO LIMPAR BASE ---
            Actions\Action::make('limpar_base')
                ->label('Limpar Tudo')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        Pauta::truncate();
                        Notification::make()->title('Base limpa!')->success()->send();
                    } catch (\Exception $e) {
                        Pauta::query()->delete();
                        Notification::make()->title('Base limpa!')->success()->send();
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }

    /**
     * Lógica de Processamento (CSV e Link)
     */
    private function processarImportacao($filePath)
    {
        // Verifica se o arquivo existe antes de tentar ler
        if (!file_exists($filePath)) {
            Notification::make()->title('Erro: Arquivo não encontrado no servidor.')->danger()->send();
            return;
        }

        try {
            $fileContent = file_get_contents($filePath);
            
            // Corrige UTF-8
            $encoding = mb_detect_encoding($fileContent, 'UTF-8, ISO-8859-1, Windows-1252', true);
            if ($encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            }

            $tempFile = tmpfile();
            fwrite($tempFile, $fileContent);
            fseek($tempFile, 0);

            // Detecta separador (; ou ,)
            $firstLine = explode("\n", $fileContent)[0];
            $separator = str_contains($firstLine, ';') ? ';' : ',';

            if (($handle = $tempFile) !== FALSE) {
                $rawHeader = fgetcsv($handle, 0, $separator);
                if (!$rawHeader) throw new \Exception("Arquivo vazio.");

                // Normaliza cabeçalho (remove acentos, espaços -> underline)
                $header = array_map(fn($item) => strtolower(trim(str_replace([' ', 'Ç', 'Ã', 'Õ', '/'], ['_', 'c', 'a', 'o', '_'], $item))), $rawHeader);

                $count = 0;
                while (($row = fgetcsv($handle, 0, $separator)) !== FALSE) {
                    if (count($header) > count($row)) $row = array_pad($row, count($header), null);
                    $dataRow = array_combine(array_slice($header, 0, count($row)), array_slice($row, 0, count($header)));

                    if (empty($dataRow['canal'])) continue;

                    // Cria/Encontra Inventário
                    $inventario = Inventario::firstOrCreate(
                        ['canal' => trim($dataRow['canal'])],
                        [
                            'tipo' => 'On',
                            'cidade' => 'Porto Alegre',
                            'endereco' => $dataRow['endereco'] ?? 'Endereço não informado',
                        ]
                    );

                    // Cria Pauta
                    Pauta::create([
                        'inventario_id'   => $inventario->id,
                        'cliente'         => $dataRow['cliente'] ?? 'Não informado',
                        'data_insercao'   => $this->parseDate($dataRow['insercao'] ?? null),
                        'comercial'       => $dataRow['comercial'] ?? 'Não informado',
                        'origem'          => $dataRow['origem'] ?? 'FOTÓGRAFO',
                        'prazo_captacao'  => $this->parseDate($dataRow['prazo_cap'] ?? null),
                        'data_captacao'   => $this->parseDate($dataRow['captacao'] ?? null),
                        'prazo_envio'     => $this->parseDate($dataRow['pz_env'] ?? null),
                        'status'          => $this->mapStatus($dataRow['status'] ?? null),
                        'data_envio_real' => $this->parseDate($dataRow['enviado'] ?? null),
                        'motivo_atraso'   => $dataRow['motivo_do_atraso'] ?? null,
                        'link_drive'      => $dataRow['link'] ?? null,
                        'endereco_manual' => $dataRow['endereco'] ?? null,
                        'obs_captacao'    => $dataRow['obs_captacao'] ?? null,
                        'obs_midia'       => $dataRow['obs'] ?? null,
                        'pi'              => $dataRow['pi'] ?? null,
                    ]);
                    $count++;
                }
                fclose($handle);

                Notification::make()->title("Sucesso! $count pautas importadas.")->success()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('Erro ao processar')->body($e->getMessage())->danger()->send();
        }
    }

    private function parseDate($date)
    {
        if (empty($date) || $date == '0000-00-00' || $date == '-') return null;
        try {
            if (str_contains($date, '/')) {
                $parts = explode('/', $date);
                $format = (isset($parts[2]) && strlen($parts[2]) == 4) ? 'd/m/Y' : 'd/m/y';
                return Carbon::createFromFormat($format, $date);
            }
            return Carbon::parse($date);
        } catch (\Exception $e) { return null; }
    }

    private function mapStatus($status)
    {
        $s = strtoupper(trim($status ?? ''));
        return match($s) {
            'ENVIADO' => 'ENVIADO',
            'MONTAGEM' => 'MONTAGEM',
            default => 'CAPTAÇÃO',
        };
    }
}