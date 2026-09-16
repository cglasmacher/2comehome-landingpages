<?php

use App\Services\OnOffice\OnOfficeClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
