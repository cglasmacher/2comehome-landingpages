<?php

namespace App\Services\OnOffice;

use App\Models\Lead;
use App\Models\LeadSyncLog;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadSyncService
{
    private const OWNER_RELATION_TYPE = 'urn:onoffice-de-ns:smart:2.5:relationTypes:estate:address:owner';

    public function __construct(private readonly OnOfficeClient $client) {}

    public function sync(Lead $lead): LeadSyncLog
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $contactPayload = [
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'source' => 'Landingpage: '.$lead->landingPage->slug,
            'utm' => $lead->utm,
            'tracking' => $lead->tracking,
        ];
        $propertyPayload = $this->buildPropertyPayload($lead);
        $remark = $this->buildRemark($lead);
        $estateNote = 'Landingpage Lead von '.$lead->landingPage->slug;
        $requestPayload = [
            'contact' => $contactPayload,
            'remark' => $remark,
            'estate' => [
                'property' => $propertyPayload,
                'internal_note' => $estateNote,
                'status' => 2,
                'status2_label' => config('landingpages.onoffice.estate_status2_label', 'in akquise'),
            ],
            'relation' => ['relationtype' => self::OWNER_RELATION_TYPE],
        ];

        $contactResponse = null;
        $estateResponse = null;
        $relationResponse = null;
        $contactId = null;
        $estateId = null;

        $stage = 'contact';
        try {
            Log::info('onOffice lead sync started', ['lead_id' => $lead->id]);
            $contactResponse = $this->client->createContactWithRemark($contactPayload, $remark);
            $contactId = data_get($contactResponse, 'external_contact_id');

            if (! $this->isSuccessful($contactResponse) || ! filled($contactId)) {
                return $this->finish($lead, 'failed', $contactId, null, $requestPayload, $contactResponse, $estateResponse, $relationResponse, $this->responseError($contactResponse) ?? 'onOffice contact ID was not returned.');
            }

            $stage = 'estate';
            $estateResponse = $this->client->createEstate($propertyPayload, $estateNote);
            $estateId = data_get($estateResponse, 'external_estate_id');

            if (! $this->isSuccessful($estateResponse) || ! filled($estateId)) {
                return $this->finish($lead, 'partial', $contactId, $estateId, $requestPayload, $contactResponse, $estateResponse, $relationResponse, $this->responseError($estateResponse) ?? 'onOffice estate ID was not returned.');
            }

            $stage = 'owner_relation';
            $relationResponse = $this->client->createOwnerRelation((string) $estateId, (string) $contactId);
            $status = $this->isSuccessful($relationResponse)
                ? ($this->isDemoSync($contactResponse, $estateResponse, $relationResponse) ? 'demo_success' : 'success')
                : 'partial';

            return $this->finish($lead, $status, $contactId, $estateId, $requestPayload, $contactResponse, $estateResponse, $relationResponse, $status === 'partial' ? $this->responseError($relationResponse) : null);
        } catch (Throwable $e) {
            Log::error('onOffice lead sync exception', [
                'lead_id' => $lead->id, 'stage' => $stage, 'exception_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return $this->finish($lead, filled($contactId) ? 'partial' : 'failed', $contactId, $estateId, $requestPayload, $contactResponse, $estateResponse, $relationResponse, $e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function buildPropertyPayload(Lead $lead): array
    {
        $property = $lead->property;

        return array_filter([
            'property_type' => $property?->property_type,
            'street' => $property?->street,
            'house_number' => $property?->house_number,
            'zip' => $property?->zip,
            'city' => $property?->city,
            'country' => $property?->country ?? 'DE',
            'construction_year' => $property?->construction_year,
            'living_area' => $property?->living_area,
            'plot_area' => $property?->plot_area,
            'rooms' => $property?->rooms,
            'description' => $this->buildEstateDescription($lead),
            'valuation' => $this->isDemoValuation($lead) ? [] : $this->estateValuation($lead),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function estateValuation(Lead $lead): array
    {
        $valuation = $lead->valuation;
        if ($valuation?->status !== 'completed') {
            return [];
        }
        $values = [];
        foreach (['estimated_value', 'range_low', 'range_high'] as $key) {
            $value = $valuation->$key;
            if (is_numeric($value) && is_finite((float) $value) && (float) $value > 0) {
                $values[$key] = round((float) $value, 2);
            }
        }
        if (isset($values['range_low'], $values['range_high']) && $values['range_low'] > $values['range_high']) {
            return [];
        }

        return $values;
    }

    private function isDemoValuation(Lead $lead): bool
    {
        return data_get($lead->valuation?->provider_response, 'confidence') === 'demo'
            || data_get($lead->valuation?->provider_response, 'provider') === 'fake_pricehubble_until_credentials_arrive';
    }

    private function buildEstateDescription(Lead $lead): string
    {
        $parts = [];
        $notes = trim((string) $lead->notes);
        if ($notes !== '') {
            // onOffice descriptions can be rendered as HTML. Treat visitor input as text.
            $parts[] = "Nachricht des Interessenten:\n".htmlspecialchars($notes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $valuation = $this->estateValuation($lead);
        if ($valuation !== []) {
            $demo = $this->isDemoValuation($lead);
            $lines = [$demo ? 'Demo-Schätzung der Landingpage (keine echte PriceHubble-Bewertung):' : 'Automatisierte Verkaufspreisschätzung der Landingpage:'];
            foreach (['estimated_value' => 'Schätzwert', 'range_low' => 'Minimum', 'range_high' => 'Maximum'] as $key => $label) {
                if (isset($valuation[$key])) {
                    $lines[] = $label.': '.number_format($valuation[$key], 2, ',', '.').' EUR';
                }
            }
            $lines[] = 'Unverbindliche Ersteinschätzung; kein festgelegter Angebotspreis.';
            $parts[] = implode("\n", $lines);
        }

        return implode("\n\n", $parts);
    }

    private function buildRemark(Lead $lead): string
    {
        $property = $lead->property;
        $valuation = $lead->valuation;

        return trim(sprintf(
            "Lead aus Landingpage %s\n\nObjekt: %s %s, %s %s\nTyp: %s\nWohnfläche: %s m²\nGrundstück: %s m²\nBaujahr: %s\nZimmer: %s\n\nPriceHubble-Einwertung:\nSchätzwert: %s EUR\nRange: %s EUR bis %s EUR\nRange-Prozent: +/- %s %%\nStatus: %s\n\nTelefonische Kontaktaufnahme erlaubt: %s\n\nNotiz des Nutzers:\n%s",
            $lead->landingPage->slug, $property?->street, $property?->house_number, $property?->zip, $property?->city,
            $property?->property_type, $property?->living_area, $property?->plot_area, $property?->construction_year, $property?->rooms,
            $valuation?->estimated_value ? number_format((float) $valuation->estimated_value, 0, ',', '.') : 'nicht verfügbar',
            $valuation?->range_low ? number_format((float) $valuation->range_low, 0, ',', '.') : 'nicht verfügbar',
            $valuation?->range_high ? number_format((float) $valuation->range_high, 0, ',', '.') : 'nicht verfügbar',
            $valuation?->range_percent ?? '-', $valuation?->status ?? 'nicht erstellt',
            $lead->phone_contact_consent_at ? 'ja' : 'nein', $lead->notes ?: '-',
        ));
    }

    private function isSuccessful(?array $response): bool
    {
        return in_array(data_get($response, 'status'), ['success', 'demo_success'], true);
    }

    private function isDemoSync(?array ...$responses): bool
    {
        return collect($responses)->every(static fn (?array $response): bool => data_get($response, 'status') === 'demo_success');
    }

    private function responseError(?array $response): ?string
    {
        return data_get($response, 'message')
            ?? data_get($response, 'raw.status.message')
            ?? data_get($response, 'raw.response.results.0.status.message');
    }

    private function finish(Lead $lead, string $status, ?string $contactId, ?string $estateId, array $requestPayload, ?array $contactResponse, ?array $estateResponse, ?array $relationResponse, ?string $errorMessage): LeadSyncLog
    {
        Log::log(in_array($status, ['success', 'demo_success'], true) ? 'info' : 'error', 'onOffice lead sync completed', [
            'lead_id' => $lead->id,
            'status' => $status,
            'external_contact_id' => $contactId,
            'external_estate_id' => $estateId,
            'stage' => ! filled($contactId) ? 'contact' : (! filled($estateId) ? 'estate' : 'owner_relation'),
            'error' => $errorMessage,
        ]);

        return LeadSyncLog::create([
            'lead_id' => $lead->id,
            'provider' => 'onoffice',
            'status' => $status,
            'external_contact_id' => $contactId,
            'external_estate_id' => $estateId,
            'request_payload' => $requestPayload,
            'response_payload' => [
                'contact' => $contactResponse,
                'estate' => $estateResponse,
                'relation' => $relationResponse,
            ],
            'error_message' => $errorMessage,
        ]);
    }
}
