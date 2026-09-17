<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewLandingPageLeadMail extends LeadValuationSummaryMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Landingpage Lead von '.$this->lead->landingPage->slug,
            replyTo: [new Address($this->lead->email, trim($this->lead->first_name.' '.$this->lead->last_name))],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.leads.team-notification');
    }
}
