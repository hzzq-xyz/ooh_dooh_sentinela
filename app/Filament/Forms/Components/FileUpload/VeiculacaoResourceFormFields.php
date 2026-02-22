<?php

// ADICIONE ESTES CAMPOS NO FORMULÁRIO do VeiculacaoResource.php

use Filament\Forms\Components\FileUpload;

// Dentro do Section::make('Dados da Veiculação')->schema([...]):

TextInput::make('cliente')
    ->label('Cliente')
    ->required()
    ->maxLength(255),

TextInput::make('email_cliente')
    ->label('E-mail do Cliente')
    ->email()
    ->placeholder('cliente@example.com')
    ->helperText('Usado para enviar o informativo automaticamente'),

TextInput::make('atendimento')
    ->label('Atendimento/Comercial')
    ->maxLength(255)
    ->default('NELA COMUNICAÇÃO'),

// ... outros campos ...

FileUpload::make('imagem_campanha')
    ->label('Imagem da Campanha')
    ->image()
    ->disk('public')
    ->directory('campanhas')
    ->imageEditor()
    ->imageEditorAspectRatios([
        '16:9',
        '4:3',
        '1:1',
    ])
    ->maxSize(5120) // 5MB
    ->helperText('Imagem que será exibida no informativo digital')
    ->columnSpanFull(),

Textarea::make('observacoes')
    ->label('Observações / Nome da Campanha')
    ->rows(3)
    ->columnSpanFull(),
