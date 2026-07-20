<?php

namespace App\Services\OnOffice;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OnOfficeClient
{
    private const ACTION_ID_CREATE = 'urn:onoffice-de-ns:smart:2.5:smartml:action:id:create';

    private const RESOURCE_TYPE_ADDRESS = 'address';

    public function createContactWithRemark(array $contactPayload, string $remark): array
    {
        $baseUrl = config('landingpages.onoffice.base_url');
        $token = (string) config('landingpages.onoffice.token');
        $secret = (string) config('landingpages.onoffice.secret');

        $debug = (bool) config('landingpages.onoffice.debug', true);

        if (! $baseUrl || ! $token || ! $secret) {
            if ($debug) {
                Log::info('onOffice API skipped: credentials missing. Demo fallback used.');
            }

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
                'timestamp' => $timestamp,
                'request_payload' => $requestBody,
            ]);
        }

        try {
            $httpResponse = Http::timeout(config('landingpages.onoffice.timeout'))
                ->acceptJson()
                ->asJson()
                ->post($baseUrl, $requestBody);

            // Capture the raw body before potential exception so we can log it on errors too.
            $responseBody = $httpResponse->json() ?? ['raw_body' => $httpResponse->body()];

            if ($debug) {
                Log::info('onOffice API raw response received', [
                    'identifier' => $identifier,
                    'status_code' => $httpResponse->status(),
                    'response_payload' => $responseBody,
                ]);
            }

            $httpResponse->throw();
        } catch (Throwable $e) {
            if ($debug) {
                Log::error('onOffice API request failed', [
                    'identifier' => $identifier,
                    'request_payload' => $requestBody,
                    'response_body' => $responseBody ?? null,
                    'exception' => $e->getMessage(),
                ]);
            }

            throw $e;
        }

        $result = data_get($responseBody, 'response.results.0');
        $status = data_get($result, 'status.errorcode', 0);
        $recordId = data_get($result, 'data.records.0.id');

        $outcome = [
            'status' => (int) $status === 0 ? 'success' : 'failed',
            'external_contact_id' => $recordId,
            'raw' => $responseBody,
        ];

        if ($debug) {
            if ($outcome['status'] === 'success') {
                Log::info('onOffice API contact created successfully', [
                    'identifier' => $identifier,
                    'external_contact_id' => $recordId,
                ]);
            } else {
                Log::warning('onOffice API returned non-zero errorcode', [
                    'identifier' => $identifier,
                    'errorcode' => $status,
                    'result' => $result,
                ]);
            }
        }

        return $outcome;
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
