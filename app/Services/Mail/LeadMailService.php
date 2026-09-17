<?php

namespace App\Services\Mail;

use App\Mail\LeadValuationSummaryMail;
use App\Mail\NewLandingPageLeadMail;
use App\Models\Lead;
use App\Models\LeadMailDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class LeadMailService
{
    /** Persist both deliveries inside the lead transaction, before external API calls. */
    public function prepare(Lead $lead): void
    {
        foreach (['prospect' => $lead->email, 'team' => config('landingpages.lead_notification_email')] as $audience => $recipient) {
            LeadMailDelivery::firstOrCreate(['lead_id' => $lead->id, 'audience' => $audience], [
                'recipient' => (string) $recipient, 'status' => 'pending',
            ]);
        }
    }

    public function sendForLead(Lead $lead): void
    {
        foreach (LeadMailDelivery::where('lead_id', $lead->id)->whereIn('status', ['pending', 'failed'])->get() as $delivery) {
            $this->send($delivery);
        }
    }

    /** Reject preview transports, including a log fallback that would mask SMTP failures. */
    public function assertLiveMailer(?string $name = null, array $visited = []): void
    {
        $name ??= config('mail.default');
        $transport = config("mail.mailers.$name.transport");
        if (in_array($name, $visited, true) || ! $transport || in_array($transport, ['log', 'array', 'null'], true)) {
            throw new RuntimeException('Kein echter Mailversand konfiguriert. MAIL_MAILER und SMTP-Zugangsdaten auf dem Server prüfen.');
        }
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = config("mail.mailers.$name.mailers", []);
            if ($children === []) {
                throw new RuntimeException('Mailer enthält keinen Versandtransport.');
            }
            foreach ($children as $child) {
                $this->assertLiveMailer($child, [...$visited, $name]);
            }
        }
    }

    public function send(LeadMailDelivery $delivery): bool
    {
        // Atomic claim: sent or already sending deliveries cannot be sent a second time.
        $claimed = LeadMailDelivery::whereKey($delivery->id)
            ->whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', 5)
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->update(['status' => 'sending', 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);
        if (! $claimed) {
            return false;
        }
        $delivery->refresh();
        try {
            $this->assertLiveMailer();
            if (! filter_var($delivery->recipient, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Ungültige Empfängeradresse für '.$delivery->audience);
            }
            $mail = $delivery->audience === 'team'
                ? new NewLandingPageLeadMail($delivery->lead)
                : new LeadValuationSummaryMail($delivery->lead);
            Mail::to($delivery->recipient)->send($mail);
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'next_attempt_at' => null, 'last_error' => null]);
            Log::info('lead email accepted by mail transport', ['lead_id' => $delivery->lead_id, 'audience' => $delivery->audience]);
            return true;
        } catch (Throwable $e) {
            // SMTP exception text can contain credentials or message content; retain only its class.
            $error = $e instanceof RuntimeException && get_class($e) === RuntimeException::class
                ? $e->getMessage() : 'Versand fehlgeschlagen ('.class_basename($e).'). SMTP-Konfiguration und Mailserver prüfen.';
            $delivery->update(['status' => 'failed', 'last_error' => $error,
                'next_attempt_at' => now()->addMinutes(min(60, 5 ** $delivery->attempts))]);
            Log::error('lead email failed', ['lead_id' => $delivery->lead_id, 'audience' => $delivery->audience,
                'attempt' => $delivery->attempts, 'error' => $error]);
            return false;
        }
    }
}
