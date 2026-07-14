<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\Valuation;
use App\Services\OnOffice\LeadSyncService;
use App\Services\PriceHubble\ValuationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class LeadSubmissionController extends Controller
{
    public function store(
        StoreLeadRequest $request,
        LandingPage $landingPage,
        ValuationService $valuationService,
        LeadSyncService $leadSyncService,
    ): RedirectResponse {
        $data = $request->validated();

        $lead = DB::transaction(function () use ($data, $request, $landingPage, $valuationService) {
            $lead = Lead::create([
                'landing_page_id' => $landingPage->id,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'source' => 'landingpage',
                'utm' => $request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']),
                'tracking' => $request->only(['gclid', 'gbraid', 'wbraid', 'fbclid']),
                'consultation_requested_at' => $request->boolean('consultation_requested') ? now() : null,
            ]);

            $lead->property()->create(array_merge([
                'country' => 'DE',
            ], $data['property'] ?? []));

            $rangePercent = (float) ($landingPage->valuation_range_percent ?: config('landingpages.default_range_percent'));
            $result = $valuationService->createForLead($lead->fresh(['property']), $rangePercent);

            Valuation::create([
                'lead_id' => $lead->id,
                'provider' => 'pricehubble',
                'estimated_value' => $result->estimatedValue,
                'range_percent' => $result->rangePercent,
                'range_low' => $result->rangeLow,
                'range_high' => $result->rangeHigh,
                'provider_payload' => null,
                'provider_response' => $result->rawResponse,
                'status' => $result->status,
                'error_message' => $result->errorMessage,
            ]);

            return $lead;
        });

        $leadSyncService->sync($lead->fresh(['landingPage', 'property', 'valuation']));

        return back()->with([
            'valuation' => $lead->valuation,
            'report_url' => route('valuation-reports.show', $lead),
        ]);
    }
}
