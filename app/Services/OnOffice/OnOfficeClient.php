<?php

namespace App\Services\OnOffice;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class OnOfficeClient
{
    private const ACTION_ID_CREATE = 'urn:onoffice-de-ns:smart:2.5:smartml:action:create';

    private const ACTION_ID_READ = 'urn:onoffice-de-ns:smart:2.5:smartml:action:read';

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
            return $this->missingCredentials('contact');
        }

        $outcome = $this->executeAction(
            self::RESOURCE_TYPE_ADDRESS,
            $this->buildAddressParameters($contactPayload, $remark),
        );

        return [
            'status' => $outcome['status'],
            'external_contact_id' => $outcome['record_id'],
            'message' => $outcome['message'],
            'raw' => $outcome['raw'],
        ];
    }

    /**
     * Create an onOffice estate from the lead's property details.
     *
     * @param  array<string, mixed>  $propertyPayload
     * @return array{status: string, external_estate_id: string|null, raw?: array<string, mixed>}
     */
    public function createEstate(array $propertyPayload, string $internalNote): array
    {
        if (! $this->hasCredentials()) {
            return $this->missingCredentials('estate');
        }

        $status2Resolution = $this->resolveEstateStatus2();

        if ($status2Resolution['status'] !== 'success' || ! filled($status2Resolution['value'])) {
            Log::error('onOffice estate status2 resolution failed', [
                'message' => $status2Resolution['error'] ?? 'Unknown status2 error',
                'label' => config('landingpages.onoffice.estate_status2_label'),
                'permittedvalues' => data_get($status2Resolution, 'raw.permittedvalues'),
            ]);

            return [
                'status' => 'failed',
                'external_estate_id' => null,
                'message' => $status2Resolution['error'] ?? 'onOffice status2 value was not resolved.',
                'raw' => [
                    'status2_resolution' => $status2Resolution['raw'],
                ],
            ];
        }

        $userResolution = $this->resolveEstateUser();
        if ($userResolution['status'] !== 'success') {
            Log::error('onOffice estate user resolution failed', ['message' => $userResolution['message']]);

            return [
                'status' => 'failed',
                'external_estate_id' => null,
                'message' => $userResolution['message'],
                'raw' => ['user_resolution' => $userResolution],
            ];
        }

        $data = $this->buildEstateParameters(
            $propertyPayload, $internalNote, (string) $status2Resolution['value'], (int) $userResolution['value'],
        );
        $configuration = $this->estateFields();
        if ($configuration['status'] !== 'success') {
            return ['status' => 'failed', 'external_estate_id' => null,
                'message' => $configuration['message'], 'raw' => []];
        }
        $noteField = trim((string) config('landingpages.onoffice.estate_note_field', 'interne_Bemerkung'));
        if ($noteField !== '' && ! isset($configuration['fields'][$noteField])) {
            unset($data[$noteField]);
            Log::warning('onOffice optional estate note field unavailable', [
                'field' => $noteField,
                'message' => 'Internal source note retained in lead_sync_logs; not sent to an unknown or public field.',
            ]);
        }
        $unknownFields = array_values(array_diff(array_keys($data), array_keys($configuration['fields'])));
        if ($unknownFields !== []) {
            $message = 'Unknown or inactive onOffice estate fields: '.implode(', ', $unknownFields);
            Log::error('onOffice estate field validation failed', ['fields' => $unknownFields, 'message' => $message]);

            return ['status' => 'failed', 'external_estate_id' => null, 'message' => $message,
                'raw' => ['unknown_fields' => $unknownFields]];
        }

        $outcome = $this->executeAction(self::RESOURCE_TYPE_ESTATE, ['data' => $data]);

        return [
            'status' => $outcome['status'],
            'external_estate_id' => $outcome['record_id'],
            'status2_resolution' => $status2Resolution['raw'],
            'message' => $outcome['message'],
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
                'status' => 'failed',
                'message' => 'onOffice credentials are missing.',
                'raw' => [],
            ];
        }

        $outcome = $this->executeAction(self::RESOURCE_TYPE_RELATION, [
            'relationtype' => self::OWNER_RELATION_TYPE,
            'parentid' => [$estateId],
            'childid' => [$contactId],
        ]);

        return [
            'status' => $outcome['status'],
            'message' => $outcome['message'],
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
     * @param  array<string, mixed>  $parameters
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
                'parameter_keys' => array_keys($parameters['data'] ?? $parameters),
            ]);
        }

        try {
            $httpResponse = Http::timeout(config('landingpages.onoffice.timeout', 20))
                ->acceptJson()->asJson()->post($baseUrl, $requestBody);
        } catch (Throwable $e) {
            // Do not retry creates automatically: a timeout may follow a successful remote write.
            $message = 'onOffice transport error ('.class_basename($e).'). Remote outcome unknown; check onOffice before retrying.';
            Log::error('onOffice API transport failed', [
                'identifier' => $identifier, 'resource_type' => $resourceType,
                'action_id' => $actionId, 'message' => $message,
            ]);

            return ['status' => 'failed', 'record_id' => null, 'message' => $message, 'raw' => []];
        }

        $body = $httpResponse->json();
        $body = is_array($body) ? $body : [];
        $result = data_get($body, 'response.results.0');
        $globalCode = data_get($body, 'status.errorcode');
        $globalStatus = data_get($body, 'status.code');
        $actionCode = data_get($result, 'status.errorcode');
        $recordId = data_get($result, 'data.records.0.id');
        $message = null;

        if (! $httpResponse->successful()) {
            $message = 'onOffice HTTP error '.$httpResponse->status();
        } elseif (($globalCode !== null && (string) $globalCode !== '0')
            || ($globalStatus !== null && (string) $globalStatus !== '200')) {
            $message = (string) (data_get($body, 'status.message') ?: 'onOffice request rejected.');
        } elseif (! is_array($result) || $actionCode === null) {
            $message = 'onOffice returned an invalid response or no action status.';
        } elseif ((string) $actionCode !== '0') {
            $message = (string) (data_get($result, 'status.message') ?: 'onOffice action rejected.');
        } elseif ($actionId === self::ACTION_ID_CREATE
            && in_array($resourceType, [self::RESOURCE_TYPE_ADDRESS, self::RESOURCE_TYPE_ESTATE], true)
            && (! is_scalar($recordId) || ! filled($recordId) || (string) $recordId === '0')) {
            $message = 'onOffice create response did not contain a record ID.';
        }

        $status = $message === null ? 'success' : 'failed';
        if ($debug || $status === 'failed') {
            Log::log($status === 'success' ? 'info' : 'error', 'onOffice API action completed', [
                'identifier' => $identifier, 'resource_type' => $resourceType, 'action_id' => $actionId,
                'http_status' => $httpResponse->status(), 'status' => $status,
                'global_errorcode' => $globalCode, 'errorcode' => $actionCode,
                'record_id' => is_scalar($recordId) ? $recordId : null, 'message' => $message,
            ]);
        }

        return [
            'status' => $status,
            'record_id' => is_scalar($recordId) && filled($recordId) ? (string) $recordId : null,
            'message' => $message,
            'raw' => $body,
        ];
    }

    /**
     * @return array{status: string, value: string|null, raw: array<string, mixed>, error?: string}
     */
    private function resolveEstateStatus2(bool $refresh = false): array
    {
        $label = trim((string) config('landingpages.onoffice.estate_status2_label', 'in akquise'));
        $configuration = $this->estateFields($refresh);
        $permittedValues = data_get($configuration, 'fields.status2.permittedvalues', []);
        if ($configuration['status'] !== 'success' || ! is_array($permittedValues)) {
            return ['status' => 'failed', 'value' => null, 'raw' => [],
                'error' => $configuration['message'] ?? 'onOffice status2 field configuration did not return permitted values.'];
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

            return [
                'status' => 'success',
                'value' => $resolvedValue,
                'raw' => ['label' => $label, 'value' => $resolvedValue],
            ];
        }

        return [
            'status' => 'failed',
            'value' => null,
            'raw' => ['label' => $label, 'permittedvalues' => $permittedValues],
            'error' => sprintf('onOffice status2 value "%s" was not found.', $label),
        ];
    }

    /**
     * @param  array<string, mixed>  $propertyPayload
     * @return array<string, mixed>
     */
    private function buildEstateParameters(array $propertyPayload, string $internalNote, string $status2, int $userId): array
    {
        $type = Str::lower(trim((string) ($propertyPayload['property_type'] ?? '')));
        $types = config('landingpages.onoffice.property_types', []);
        if ($type !== '' && ! isset($types[$type])) {
            throw new InvalidArgumentException('Unsupported onOffice property type. Check landingpages.onoffice.property_types.');
        }
        $country = strtoupper($propertyPayload['country'] ?? 'DE');
        $country = config('landingpages.onoffice.country_codes.'.$country, $country);
        if (! preg_match('/^[A-Z]{3}$/', $country)) {
            throw new InvalidArgumentException('Missing ISO alpha-3 country mapping for onOffice.');
        }

        $noteField = trim((string) config('landingpages.onoffice.estate_note_field', 'interne_Bemerkung'));
        $noteData = $noteField === '' ? [] : [$noteField => $internalNote];

        return array_filter(array_merge($noteData, $types[$type] ?? [], [
            'status' => self::INACTIVE_ESTATE_STATUS,
            'status2' => $status2,
            'benutzer' => $userId,
            'nutzungsart' => 'wohnen',
            'vermarktungsart' => 'kauf',
            'strasse' => $propertyPayload['street'] ?? null,
            'hausnummer' => $propertyPayload['house_number'] ?? null,
            'plz' => $propertyPayload['zip'] ?? null,
            'ort' => $propertyPayload['city'] ?? null,
            'land' => $country,
            'baujahr' => $propertyPayload['construction_year'] ?? null,
            'wohnflaeche' => $propertyPayload['living_area'] ?? null,
            'grundstuecksflaeche' => $propertyPayload['plot_area'] ?? null,
            'anzahl_zimmer' => $propertyPayload['rooms'] ?? null,
        ]), static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $contactPayload
     * @return array<string, mixed>
     */
    private function buildAddressParameters(array $contactPayload, string $remark): array
    {
        return array_filter([
            'Vorname' => $contactPayload['first_name'] ?? null,
            'Name' => $contactPayload['last_name'] ?? null,
            'email' => $contactPayload['email'] ?? null,
            'phone' => $contactPayload['phone'] ?? null,
            // 'kommentar' => $remark, */ This field is commented out because it is not supported by the onOffice API for address creation.
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array{status: string, external_contact_id?: string, external_estate_id?: string, raw: array<string, mixed>}
     */
    private function missingCredentials(string $resource): array
    {
        $message = 'onOffice base URL, token or secret is missing. No data was sent.';
        Log::error('onOffice credentials missing', ['resource_type' => $resource]);

        return [
            'status' => 'failed',
            $resource === 'contact' ? 'external_contact_id' : 'external_estate_id' => null,
            'message' => $message,
            'raw' => [],
        ];
    }

    private function cacheKey(string $suffix): string
    {
        return 'onoffice.estate.'.hash('sha256', config('landingpages.onoffice.base_url').'|'
            .config('landingpages.onoffice.token').'|'.$suffix);
    }

    private function resolveEstateUser(bool $refresh = false): array
    {
        $configured = config('landingpages.onoffice.estate_user_id');
        if (filled($configured)) {
            if (! ctype_digit((string) $configured) || (int) $configured <= 0) {
                return ['status' => 'failed', 'message' => 'ONOFFICE_ESTATE_USER_ID must be a positive numeric user ID.'];
            }

            return ['status' => 'success', 'value' => (int) $configured];
        }
        $initials = trim((string) config('landingpages.onoffice.estate_user_initials', 'CG'));
        if ($initials === '') {
            return ['status' => 'failed', 'message' => 'ONOFFICE_ESTATE_USER_INITIALS is empty.'];
        }
        $key = $this->cacheKey('user.'.Str::lower($initials));
        $cached = $refresh ? null : Cache::get($key);
        if (filled($cached)) {
            return ['status' => 'success', 'value' => (int) $cached];
        }
        $outcome = $this->executeAction('user', [
            'data' => ['Kuerzel'],
            'filter' => ['Kuerzel' => [['op' => '=', 'val' => $initials]]],
            'listlimit' => 500,
        ], self::ACTION_ID_READ);
        if ($outcome['status'] !== 'success') {
            return ['status' => 'failed', 'message' => $outcome['message'].' Enable API user read permission or set ONOFFICE_ESTATE_USER_ID.'];
        }
        $users = collect(data_get($outcome, 'raw.response.results.0.data.records', []))
            ->filter(fn ($user) => Str::lower(trim((string) data_get($user, 'elements.Kuerzel'))) === Str::lower($initials));
        if ($users->count() !== 1 || ! ctype_digit((string) data_get($users->first(), 'id')) || (int) data_get($users->first(), 'id') <= 0) {
            return ['status' => 'failed', 'message' => 'onOffice user initials did not resolve to exactly one user. Set ONOFFICE_ESTATE_USER_ID.'];
        }
        $id = (int) $users->first()['id'];
        Cache::put($key, $id, now()->addDay());

        return ['status' => 'success', 'value' => $id];
    }

    /** Only actual field definitions are accepted; the module label is not a field. */
    private function estateFields(bool $refresh = false): array
    {
        $key = $this->cacheKey('fields.v1');
        if ($refresh) {
            Cache::forget($key);
        } else {
            $cached = Cache::get($key);
            if (is_array($cached) && $cached !== []) {
                return ['status' => 'success', 'fields' => $cached, 'message' => null];
            }
        }
        $outcome = $this->executeAction(self::RESOURCE_TYPE_FIELDS, [
            'labels' => true, 'language' => 'DEU', 'modules' => ['estate'],
        ], self::ACTION_ID_GET);
        $module = collect(data_get($outcome, 'raw.response.results.0.data.records', []))->firstWhere('id', 'estate');
        $fields = data_get($module, 'elements', []);
        $fields = is_array($fields) ? array_filter($fields, static fn ($field) => is_array($field) && isset($field['type'])) : [];
        if ($outcome['status'] !== 'success' || $fields === []) {
            return ['status' => 'failed', 'fields' => [],
                'message' => $outcome['message'] ?? 'onOffice did not return estate field definitions.'];
        }
        Cache::put($key, $fields, now()->addSeconds(max(60, (int) config('landingpages.onoffice.estate_status2_cache_ttl', 86400))));

        return ['status' => 'success', 'fields' => $fields, 'message' => null];
    }

    private function diagnoseEstateFields(array $configuration): array
    {
        if ($configuration['status'] !== 'success') {
            return ['status' => 'failed', 'message' => $configuration['message']];
        }
        // Check every field that the form can send, without using real lead data.
        $expected = array_keys($this->buildEstateParameters([
            'property_type' => 'einfamilienhaus', 'street' => '-', 'house_number' => '-',
            'zip' => '-', 'city' => '-', 'construction_year' => 2000,
            'living_area' => 1, 'plot_area' => 1, 'rooms' => 1,
        ], '-', '-', 1));
        $noteField = trim((string) config('landingpages.onoffice.estate_note_field', 'interne_Bemerkung'));
        $unknown = array_values(array_diff($expected, array_keys($configuration['fields']), [$noteField]));
        $candidates = [];
        foreach ($configuration['fields'] as $name => $field) {
            $label = (string) ($field['label'] ?? '');
            if (Str::contains(Str::lower($name.' '.$label), ['bemerk', 'intern', 'notiz'])) {
                $candidates[$name] = $label;
            }
        }

        return [
            'status' => $unknown === [] ? 'success' : 'failed',
            'unknown_fields' => $unknown,
            'note_field' => $noteField,
            'note_field_available' => $noteField !== '' && isset($configuration['fields'][$noteField]),
            'note_field_candidates' => $candidates,
        ];
    }

    /** Read-only: no contacts, estates or relations are created. */
    public function diagnose(): array
    {
        if (! $this->hasCredentials()) {
            return ['credentials' => ['status' => 'failed', 'message' => 'onOffice credentials are missing.']];
        }
        $status2 = $this->resolveEstateStatus2(true);

        return [
            'status2' => [
                'status' => $status2['status'], 'value' => $status2['value'],
                'message' => $status2['error'] ?? null,
                'permittedvalues' => data_get($status2, 'raw.permittedvalues'),
            ],
            'user' => $this->resolveEstateUser(true),
            'estate_fields' => $this->diagnoseEstateFields($this->estateFields()),
        ];
    }

    /**
     * New HMAC method (hmac_sha256, hmac_version = 2).
     *
     * hmac = base64(hash_hmac('sha256', timestamp . token . resourcetype . actionid, secret, true))
     */
    private function createHmac(string $token, string $secret, int $timestamp, string $resourceType, string $actionId): string
    {
        $fields = $timestamp.$token.$resourceType.$actionId;

        return base64_encode(hash_hmac('sha256', $fields, $secret, true));
    }
}
