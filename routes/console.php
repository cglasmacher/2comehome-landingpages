<?php

use App\Services\OnOffice\OnOfficeClient;
use App\Services\Mail\LeadMailService;
use App\Models\LeadMailDelivery;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('onoffice:diagnose', function (OnOfficeClient $client) {
    try {
        $result = $client->diagnose();
        $failed = collect($result)->contains(fn ($check) => $check['status'] !== 'success');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        Log::log($failed ? 'error' : 'info', 'onOffice configuration diagnosis', $result);

        return $failed ? 1 : 0;
    } catch (Throwable $e) {
        $this->error('Diagnose fehlgeschlagen: '.$e->getMessage());

        return 1;
    }
})->purpose('Prueft Status 2 und Bearbeiter ohne Datensaetze in onOffice anzulegen');

Artisan::command('leads:mail-status', function (LeadMailService $service) {
    try {
        $service->assertLiveMailer();
        $this->info('Versandtransport konfiguriert: '.config('mail.default').' (keine Testmail versendet).');
    } catch (Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }
    $this->table(['Status', 'Anzahl'], LeadMailDelivery::selectRaw('status, count(*) as total')->groupBy('status')->get()->map(fn ($row) => [$row->status, $row->total])->all());
    return 0;
})->purpose('Zeigt Mail-Konfiguration und Versandstatus ohne eine E-Mail zu senden');

Artisan::command('leads:send-mail {--lead= : Versand auf eine Lead-ID begrenzen} {--retry : Fehlgeschlagene Zustellungen dieses Leads sofort erneut versuchen}', function (LeadMailService $service) {
    if ($this->option('retry')) {
        if (! ctype_digit((string) $this->option('lead'))) {
            $this->error('--retry benötigt eine konkrete --lead=ID.');
            return 1;
        }
        LeadMailDelivery::where('lead_id', $this->option('lead'))->where('status', 'failed')
            ->update(['attempts' => 0, 'next_attempt_at' => null]);
    }
    $query = LeadMailDelivery::whereIn('status', ['pending', 'failed'])->where('attempts', '<', 5)
        ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()));
    if ($this->option('lead') !== null) {
        if (! ctype_digit($this->option('lead'))) {
            $this->error('Bitte eine numerische Lead-ID angeben.');
            return 1;
        }
        $query->where('lead_id', $this->option('lead'));
    }
    $failed = false;
    foreach ($query->orderBy('id')->limit(50)->get() as $delivery) {
        $service->send($delivery);
        $failed = $failed || $delivery->fresh()->status === 'failed';
    }
    return $failed ? 1 : 0;
})->purpose('Sendet fällige Lead-E-Mails; bereits versendete E-Mails werden übersprungen');

Schedule::command('leads:send-mail')->everyMinute()->withoutOverlapping();
