<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $appliedFilters
     */
    public function __construct(
        public array $summary,
        public array $appliedFilters,
        public string $pdfBinary,
        public string $reportFolio,
        public User $requester,
        public ?string $notes = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[GTS AIFA] Reporte Operativo de Incidencias #{$this->reportFolio}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfBinary,
                "Reporte_GTS_{$this->reportFolio}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
