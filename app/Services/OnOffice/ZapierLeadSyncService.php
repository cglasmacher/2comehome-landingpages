<?php

namespace App\Services\OnOffice;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZapierLeadSyncService
{
    public function send(Lead $lead): array
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $webhookUrl = config('landingpages.onoffice.zapier_webhook_url');

        $payload = [
            'source' => 'Landingpage: ' . $lead->landingPage->slug,
            'contact' => [
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'notes' => $lead->notes,
            ],
            'property' => $lead->property?->toArray() ?? [],
            'valuation' => $lead->valuation?->toArray() ?? [],
            'consultation_requested' => $lead->consultation_requested_at !== null,
            'utm' => $lead->utm,
            'tracking' => $lead->tracking,
        ];

        if (! $webhookUrl) {
            Log::info('onOffice Zapier webhook URL not configured. Skipped sync.', [
                'lead_id' => $lead->id,
            ]);

            return ['status' => 'skipped', 'reason' => 'webhook_url_missing'];
        }

        try {
            $response = Http::timeout(config('landingpages.onoffice.timeout', 20))
                ->acceptJson()
                ->asJson()
                ->post($webhookUrl, $payload)
                ->throw()
                ->json();

            Log::info('onOffice Zapier sync successful', [
                'lead_id' => $lead->id,
                'response' => $response,
            ]);

            return ['status' => 'success', 'response' => $response];
        } catch (Throwable $e) {
            Log::error('onOffice Zapier sync failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
}
