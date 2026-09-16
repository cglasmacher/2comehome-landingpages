<?php

namespace App\Services\OnOffice;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OnOfficeClient
{
    private const ACTION_ID_CREATE = 'urn:onoffice-de-ns:smart:2.5:smartml:action:create';

    private const ACTION_ID_GET = 'urn:onoffice-de-ns:smart:2.5:smartml:action:get';

    private const RESOURCE_TYPE_ADDRESS = 'address';

    private const RESOURCE_TYPE_ESTATE = 'estate';

    private const RESOURCE_TYPE_FIELDS = 'fields';

    private const INACTIVE_ESTATE_STATUS = 2;

    private const RESOURCE_TYPE_RELATION = 'relation';

    private const OWNER_RELATION_TYPE = 'urn:onoffice-de-ns:smart:2.5:relationTypes:estate:address:owner';

    public function createContactWithRemark(array $contactPayload, string $remark): array
    {
        if (! $this->hasCredentials()) {
            return $this->demoOutcome('contact');
        }

        $outcome = $this->executeAction(
            self::RESOURCE_TYPE_ADDRESS,
            $this->buildAddressParameters($contactPayload, $remark),
        );

        return [
            'status' => $outcome['status'],
            'external_contact_id' => $outcome['record_id'],
            'raw' => $outcome['raw'],
        ];
    }

    /**
     * Create an onOffice estate from the lead's property details.
     *
     * @param array<string, mixed> $propertyPayload
     * @return array{status: string, external_estate_id: string|null, raw?: array<string, mixed>}
     */
    public function createEstate(array $propertyPayload, string $internalNote): array
    {
        if (! $this->hasCredentials()) {
            return $this->demoOutcome('estate');
        }

        $status2Resolution = $this->resolveEstateStatus2();

        if ($status2Resolution['status'] !== 'success' || ! filled($status2Resolution['value'])) {
            return [
                'status' => 'failed',
                'external_estate_id' => null,
                'message' => $status2Resolution['error'] ?? 'onOffice status2 value was not resolved.',
                'raw' => [
                    'status2_resolution' => $status2Resolution['raw'],
                ],
            ];
        }

        $outcome = $this->executeAction(
            self::RESOURCE_TYPE_ESTATE,
            $this->buildEstateParameters($propertyPayload, $internalNote, (string) $status2Resolution['value']),
        );

        return [
            'status' => $outcome['status'],
            'external_estate_id' => $outcome['record_id'],
            'status2_resolution' => $status2Resolution['raw'],
            'raw' => $outcome['raw'],
        ];
    }

    /**
     * Link an address record to an estate as its owner.
     *
     * @return array{status: string, raw?: array<string, mixed>}
     */
    public function createOwnerRelation(string $estateId, string $contactId): array
    {
        if (! $this->hasCredentials()) {
            return [
                'status' => 'demo_success',
                'raw' => ['demo' => true, 'relationtype' => self::OWNER_RELATION_TYPE],
            ];
        }

        $outcome = $this->executeAction(self::RESOURCE_TYPE_RELATION, [
            'relationtype' => self::OWNER_RELATION_TYPE,
            'parentid' => $estateId,
            'childid' => $contactId,
        ]);

        return [
            'status' => $outcome['status'],
            'raw' => $outcome['raw'],
        ];
    }

    private function hasCredentials(): bool
    {
        return filled(config('landingpages.onoffice.base_url'))
            && filled(config('landingpages.onoffice.token'))
            && filled(config('landingpages.onoffice.secret'));
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array{status: string, record_id: string|null, raw: array<string, mixed>}
     */
    private function executeAction(string $resourceType, array $parameters, string $actionId = self::ACTION_ID_CREATE): array
    {
        $baseUrl = (string) config('landingpages.onoffice.base_url');
        $token = (string) config('landingpages.onoffice.token');
        $secret = (string) config('landingpages.onoffice.secret');
        $debug = (bool) config('landingpages.onoffice.debug', true);
        $timestamp = time();
        $identifier = (string) Str::uuid();

        $action = [
            'actionid' => $actionId,
            'resourceid' => '',
            'resourcetype' => $resourceType,
            'identifier' => $identifier,
            'timestamp' => $timestamp,
            'hmac' => $this->createHmac($token, $secret, $timestamp, $resourceType, $actionId),
            'hmac_version' => '2',
            'parameters' => $parameters,
        ];

        $requestBody = [
            'token' => $token,
            'request' => [
                'actions' => [$action],
            ],
        ];

        if ($debug) {
            Log::info('onOffice API request initiated', [
                'url' => $baseUrl,
                'identifier' => $identifier,
                'resource_type' => $resourceType,
                'timestamp' => $timestamp,
                'parameter_keys' => array_keys($parameters),
            ]);
        }

        try {
            $httpResponse = Http::timeout(config('landingpages.onoffice.timeout'))
                ->acceptJson()
                ->asJson()
                ->post($baseUrl, $requestBody);

            $responseBody = $httpResponse->json() ?? ['raw_body' => $httpResponse->body()];

            if ($debug) {
                Log::info('onOffice API raw response received', [
                    'identifier' => $identifier,
                    'resource_type' => $resourceType,
                    'status_code' => $httpResponse->status(),
                    'errorcode' => data_get($responseBody, 'response.results.0.status.errorcode'),
                ]);
            }

            $httpResponse->throw();
        } catch (Throwable $e) {
            if ($debug) {
                Log::error('onOffice API request failed', [
                    'identifier' => $identifier,
                    'resource_type' => $resourceType,
                    'exception' => $e->getMessage(),
                ]);
            }

            throw $e;
        }

        $result = data_get($responseBody, 'response.results.0', []);
        $errorCode = (int) data_get($result, 'status.errorcode', 0);
        $recordId = data_get($result, 'data.records.0.id');
        $status = $errorCode === 0 ? 'success' : 'failed';

        if ($debug) {
            Log::log($status === 'success' ? 'info' : 'warning', 'onOffice API action completed', [
                'identifier' => $identifier,
                'resource_type' => $resourceType,
                'record_id' => $recordId,
                'errorcode' => $errorCode,
                'message' => data_get($result, 'status.message'),
            ]);
        }

        return [
            'status' => $status,
            'record_id' => $recordId,
            'raw' => $responseBody,
        ];
    }

    /**
     * @return array{status: string, value: string|null, raw: array<string, mixed>, error?: string}
     */
    private function resolveEstateStatus2(): array
    {
        $label = trim((string) config('landingpages.onoffice.estate_status2_label', 'in akquise'));
        $cacheKey = 'onoffice.estate.status2.' . md5(Str::lower($label));
        $cachedValue = Cache::get($cacheKey);

        if (filled($cachedValue)) {
            return [
                'status' => 'success',
                'value' => (string) $cachedValue,
                'raw' => ['cached' => true, 'label' => $label],
            ];
        }

        try {
            $outcome = $this->executeAction(self::RESOURCE_TYPE_FIELDS, [
                'labels' => true,
                'language' => 'DEU',
                'modules' => ['estate'],
                'fieldList' => ['status2'],
            ], self::ACTION_ID_GET);
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'value' => null,
                'raw' => ['exception' => $e->getMessage(), 'label' => $label],
                'error' => 'onOffice status2 field configuration request failed: ' . $e->getMessage(),
            ];
        }

        $field = data_get($outcome, 'raw.response.results.0.data.records.0.elements.status2', []);
        $permittedValues = data_get($field, 'permittedvalues', []);

        if ($outcome['status'] !== 'success' || ! is_array($permittedValues)) {
            return [
                'status' => 'failed',
                'value' => null,
                'raw' => $outcome['raw'],
                'error' => 'onOffice status2 field configuration did not return permitted values.',
            ];
        }

        $normalizedLabel = Str::lower($label);

        foreach ($permittedValues as $value => $permittedLabel) {
            if (is_array($permittedLabel)) {
                $permittedLabel = $permittedLabel['label'] ?? $permittedLabel['value'] ?? null;
            }

            if (! is_string($permittedLabel) || Str::lower(trim($permittedLabel)) !== $normalizedLabel) {
                continue;
            }

            $resolvedValue = (string) $value;
            Cache::put(
                $cacheKey,
                $resolvedValue,
                now()->addSeconds(max(60, (int) config('landingpages.onoffice.estate_status2_cache_ttl', 86400))),
            );

            return [
                'status' => 'success',
                'value' => $resolvedValue,
                'raw' => ['cached' => false, 'label' => $label, 'value' => $resolvedValue, 'response' => $outcome['raw']],
            ];
        }

        return [
            'status' => 'failed',
            'value' => null,
            'raw' => ['label' => $label, 'permittedvalues' => $permittedValues, 'response' => $outcome['raw']],
            'error' => sprintf('onOffice status2 value "%s" was not found.', $label),
        ];
    }

    /**
     * @param array<string, mixed> $propertyPayload
     * @return array<string, mixed>
     */
    private function buildEstateParameters(array $propertyPayload, string $internalNote, string $status2): array
    {
        return array_filter([
            'status' => self::INACTIVE_ESTATE_STATUS,
            'status2' => $status2,
            'objektart' => $propertyPayload['property_type'] ?? null,
            'strasse' => $propertyPayload['street'] ?? null,
            'hausnummer' => $propertyPayload['house_number'] ?? null,
            'plz' => $propertyPayload['zip'] ?? null,
            'ort' => $propertyPayload['city'] ?? null,
            'land' => $propertyPayload['country'] ?? null,
            'baujahr' => $propertyPayload['construction_year'] ?? null,
            'wohnflaeche' => $propertyPayload['living_area'] ?? null,
            'grundstuecksflaeche' => $propertyPayload['plot_area'] ?? null,
            'anzahl_zimmer' => $propertyPayload['rooms'] ?? null,
            'interne_Bemerkung' => $internalNote,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param array<string, mixed> $contactPayload
     * @return array<string, mixed>
     */
    private function buildAddressParameters(array $contactPayload, string $remark): array
    {
        return array_filter([
            'Vorname' => $contactPayload['first_name'] ?? null,
            'Name' => $contactPayload['last_name'] ?? null,
            'email' => $contactPayload['email'] ?? null,
            'phone' => $contactPayload['phone'] ?? null,
            //'kommentar' => $remark, */ This field is commented out because it is not supported by the onOffice API for address creation.
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array{status: string, external_contact_id?: string, external_estate_id?: string, raw: array<string, mixed>}
     */
    private function demoOutcome(string $resource): array
    {
        $id = 'demo-' . $resource . '-' . now()->timestamp;

        if ((bool) config('landingpages.onoffice.debug', true)) {
            Log::info('onOffice API skipped: credentials missing. Demo fallback used.', [
                'resource_type' => $resource,
            ]);
        }

        return [
            'status' => 'demo_success',
            $resource === 'contact' ? 'external_contact_id' : 'external_estate_id' => $id,
            'raw' => ['demo' => true, 'resource_type' => $resource],
        ];
    }

    /**
     * New HMAC method (hmac_sha256, hmac_version = 2).
     *
     * hmac = base64(hash_hmac('sha256', timestamp . token . resourcetype . actionid, secret, true))
     */
    private function createHmac(string $token, string $secret, int $timestamp, string $resourceType, string $actionId): string
    {
        $fields = $timestamp . $token . $resourceType . $actionId;

        return base64_encode(hash_hmac('sha256', $fields, $secret, true));
    }
}
