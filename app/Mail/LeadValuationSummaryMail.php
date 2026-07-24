<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
        return [];
    }
}
