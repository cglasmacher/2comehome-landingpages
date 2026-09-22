<?php

namespace App\Console\Commands;

use App\Models\LeadSyncLog;
use App\Services\OnOffice\OnOfficeClient;
use Illuminate\Console\Command;

class BackfillOnOfficeReferenceNumbers extends Command
{
    protected $signature = 'onoffice:backfill-reference-numbers {--force : Refresh values even when already stored}';

    protected $description = 'Fetch KdNr and ImmoNr for existing onOffice lead sync logs';

    public function handle(OnOfficeClient $client): int
    {
        $query = LeadSyncLog::query()
            ->where('provider', 'onoffice')
            ->where(function ($query): void {
                $query->whereNotNull('external_contact_id')
                    ->orWhereNotNull('external_estate_id');
            });

        if (! $this->option('force')) {
            $query->where(function ($query): void {
                $query->whereNull('onoffice_kdnr')
                    ->orWhereNull('onoffice_immonr');
            });
        }

        $count = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(100, function ($logs) use ($client, &$count, &$failed): void {
            foreach ($logs as $log) {
                $updates = [];

                if ($log->external_contact_id && ($this->option('force') || ! $log->onoffice_kdnr)) {
                    $result = $client->readContactReferenceNumber((string) $log->external_contact_id);
                    if ($result['status'] === 'success' && filled($result['value'])) {
                        $updates['onoffice_kdnr'] = $result['value'];
                    } else {
                        $failed++;
                    }
                }

                if ($log->external_estate_id && ($this->option('force') || ! $log->onoffice_immonr)) {
                    $result = $client->readEstateReferenceNumber((string) $log->external_estate_id);
                    if ($result['status'] === 'success' && filled($result['value'])) {
                        $updates['onoffice_immonr'] = $result['value'];
                    } else {
                        $failed++;
                    }
                }

                if ($updates !== []) {
                    $log->update($updates);
                    $count++;
                }
            }
        });

        $this->info($count.' Sync-Logs wurden aktualisiert.');

        if ($failed > 0) {
            $this->warn($failed.' onOffice-Referenzwerte konnten nicht gelesen werden.');
        }

        return self::SUCCESS;
    }
}
