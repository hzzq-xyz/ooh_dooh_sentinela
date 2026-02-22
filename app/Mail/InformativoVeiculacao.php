<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class InformativoVeiculacao extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $veiculacoes,
        public string $nomeCliente,
        public string $nomeCampanha,
        public string $atendimento,
        public string $dataInicio,
        public string $dataFim,
        public ?string $imagemCampanha = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Informativo de Veiculação - ' . $this->nomeCliente,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.informativo-veiculacao',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
