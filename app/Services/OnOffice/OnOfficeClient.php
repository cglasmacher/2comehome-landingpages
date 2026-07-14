<?php

namespace App\Services\OnOffice;

use Illuminate\Support\Facades\Http;

class OnOfficeClient
{
    public function createContactWithRemark(array $contactPayload, string $remark): array
    {
        $baseUrl = config('landingpages.onoffice.base_url');

        if (!$baseUrl) {
            return [
                'status' => 'demo_success',
                'external_contact_id' => 'demo-' . now()->timestamp,
                'message' => 'onOffice credentials missing. Payload was not sent.',
            ];
        }

        // TODO: Replace with exact onOffice enterprise API signing/action format.
        return Http::timeout(config('landingpages.onoffice.timeout'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Token' => (string) config('landingpages.onoffice.token'),
            ])
            ->post(rtrim($baseUrl, '/') . '/contacts', [
                'contact' => $contactPayload,
                'remark' => $remark,
            ])
            ->throw()
            ->json();
    }
}
