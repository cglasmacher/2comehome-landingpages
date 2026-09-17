<?php

namespace App\Mail;

use App\Models\Lead;
use App\Services\Pdf\ValuationReportPdfService;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class LeadValuationSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead)
    {
        $this->lead->loadMissing(['landingPage', 'property', 'valuation']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Immobilien-Einschätzung von 2 COME HOME Immobilien',
            replyTo: [new Address(config('landingpages.lead_notification_email'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leads.valuation-summary',
        );
    }

    public function attachments(): array
    {
        return [Attachment::fromData(
            fn () => app(ValuationReportPdfService::class)->bytes($this->lead),
            'Immobilienbewertung-'.$this->lead->uuid.'.pdf',
        )->withMime('application/pdf')];
    }
}
