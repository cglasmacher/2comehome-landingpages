<?php

namespace App\Services\OnOffice;

use App\Models\Lead;
use App\Models\LeadSyncLog;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadSyncService
{
    public function __construct(private readonly OnOfficeClient $client) {}

    public function sync(Lead $lead): LeadSyncLog
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $contactPayload = [
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'source' => 'Landingpage: ' . $lead->landingPage->slug,
            'utm' => $lead->utm,
            'tracking' => $lead->tracking,
        ];

        $remark = $this->buildRemark($lead);

        try {
            $response = $this->client->createContactWithRemark($contactPayload, $remark);

            Log::info('onOffice lead sync successful', [
                'lead_id' => $lead->id,
                'external_contact_id' => data_get($response, 'external_contact_id'),
            ]);

            return LeadSyncLog::create([
                'lead_id' => $lead->id,
                'provider' => 'onoffice',
                'status' => 'success',
                'external_contact_id' => data_get($response, 'external_contact_id'),
                'request_payload' => ['contact' => $contactPayload, 'remark' => $remark],
                'response_payload' => $response,
            ]);
        } catch (Throwable $e) {
            report($e);

            Log::error('onOffice lead sync failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'request_payload' => ['contact' => $contactPayload, 'remark' => $remark],
            ]);

            return LeadSyncLog::create([
                'lead_id' => $lead->id,
                'provider' => 'onoffice',
                'status' => 'failed',
                'request_payload' => ['contact' => $contactPayload, 'remark' => $remark],
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function buildRemark(Lead $lead): string
    {
        $property = $lead->property;
        $valuation = $lead->valuation;

        return trim(sprintf(
            "Lead aus Landingpage %s\n\nObjekt: %s %s, %s %s\nTyp: %s\nWohnfläche: %s m²\nGrundstück: %s m²\nBaujahr: %s\nZimmer: %s\n\nPriceHubble-Einwertung:\nSchätzwert: %s EUR\nRange: %s EUR bis %s EUR\nRange-Prozent: +/- %s %%\nStatus: %s\n\nCTA Erstberatung: %s\n\nNotiz des Nutzers:\n%s",
            $lead->landingPage->slug,
            $property?->street,
            $property?->house_number,
            $property?->zip,
            $property?->city,
            $property?->property_type,
            $property?->living_area,
            $property?->plot_area,
            $property?->construction_year,
            $property?->rooms,
            $valuation?->estimated_value ? number_format((float) $valuation->estimated_value, 0, ',', '.') : 'nicht verfügbar',
            $valuation?->range_low ? number_format((float) $valuation->range_low, 0, ',', '.') : 'nicht verfügbar',
            $valuation?->range_high ? number_format((float) $valuation->range_high, 0, ',', '.') : 'nicht verfügbar',
            $valuation?->range_percent ?? '-',
            $valuation?->status ?? 'nicht erstellt',
            $lead->consultation_requested_at ? 'ja' : 'nein',
            $lead->notes ?: '-'
        ));
    }
}
