<?php

namespace App\Services\OnOffice;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OnOfficeClient
{
    private const ACTION_ID_CREATE = 'urn:onoffice-de-ns:smart:2.5:smartml:action:id:create';

    private const RESOURCE_TYPE_ADDRESS = 'address';

    public function createContactWithRemark(array $contactPayload, string $remark): array
    {
        $baseUrl = config('landingpages.onoffice.base_url');
        $token = (string) config('landingpages.onoffice.token');
        $secret = (string) config('landingpages.onoffice.secret');

        if (! $baseUrl || ! $token || ! $secret) {
            return [
                'status' => 'demo_success',
                'external_contact_id' => 'demo-' . now()->timestamp,
                'message' => 'onOffice credentials missing. Payload was not sent.',
            ];
        }

        $parameters = $this->buildAddressParameters($contactPayload, $remark);
        $timestamp = time();
        $identifier = (string) Str::uuid();

        $action = [
            'actionid' => self::ACTION_ID_CREATE,
            'resourceid' => '',
            'resourcetype' => self::RESOURCE_TYPE_ADDRESS,
            'identifier' => $identifier,
            'timestamp' => $timestamp,
            'hmac' => $this->createHmac($token, $secret, $timestamp, self::RESOURCE_TYPE_ADDRESS, self::ACTION_ID_CREATE),
            'hmac_version' => '2',
            'parameters' => $parameters,
        ];

        $response = Http::timeout(config('landingpages.onoffice.timeout'))
            ->acceptJson()
            ->asJson()
            ->post($baseUrl, [
                'token' => $token,
                'request' => [
                    'actions' => [$action],
                ],
            ])
            ->throw()
            ->json();

        $result = data_get($response, 'response.results.0');
        $status = data_get($result, 'status.errorcode', 0);
        $recordId = data_get($result, 'data.records.0.id');

        return [
            'status' => (int) $status === 0 ? 'success' : 'failed',
            'external_contact_id' => $recordId,
            'raw' => $response,
        ];
    }

    /**
     * New HMAC method (hmac_sha256, hmac_version = 2).
     *
     * hmac = base64( hash_hmac('sha256', timestamp . token . resourcetype . actionid, secret, true) )
     *
     * @see https://apidoc.onoffice.de/onoffice-api-request/request-elemente/action/#hmac
     */
    private function createHmac(string $token, string $secret, int $timestamp, string $resourceType, string $actionId): string
    {
        $fields = $timestamp . $token . $resourceType . $actionId;

        return base64_encode(hash_hmac('sha256', $fields, $secret, true));
    }

    /**
     * Maps the internal contact payload to onOffice's default address field names.
     * Adjust these keys if the enterprise instance uses custom field labels
     * (Extras -> Einstellungen -> Administration -> Eingabefelder).
     *
     * @return array<string, mixed>
     */
    private function buildAddressParameters(array $contactPayload, string $remark): array
    {
        return array_filter([
            'Vorname' => $contactPayload['first_name'] ?? null,
            'Name' => $contactPayload['last_name'] ?? null,
            'email' => $contactPayload['email'] ?? null,
            'phone' => $contactPayload['phone'] ?? null,
            'kommentar' => $remark,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
