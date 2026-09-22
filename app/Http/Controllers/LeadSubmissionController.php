<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Services\Mail\LeadMailService;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\Valuation;
use App\Services\OnOffice\LeadSyncService;
use App\Services\Valuation\ValuationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadSubmissionController extends Controller
{
    public function store(
        StoreLeadRequest $request,
        LandingPage $landingPage,
        ValuationService $valuationService,
        LeadSyncService $leadSyncService,
        LeadMailService $leadMailService,
    ): RedirectResponse {
        $data = $request->validated();

        $lead = DB::transaction(function () use ($data, $request, $landingPage, $valuationService, $leadMailService) {
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
                'phone_contact_consent_at' => now(),
                'valuation_disclaimer_accepted_at' => now(),
            ]);

            $lead->property()->create(array_merge([
                'country' => 'DE',
            ], $data['property'] ?? []));

            $rangePercent = (float) ($landingPage->valuation_range_percent ?: config('landingpages.default_range_percent'));
            $result = $valuationService->createForLead($lead->fresh(['property']), $rangePercent);

            Valuation::create([
                'lead_id' => $lead->id,
                'provider' => $result->provider,
                'estimated_value' => $result->estimatedValue,
                'range_percent' => $result->rangePercent,
                'range_low' => $result->rangeLow,
                'range_high' => $result->rangeHigh,
                'provider_payload' => null,
                'provider_response' => $result->rawResponse,
                'status' => $result->status,
                'error_message' => $result->errorMessage,
            ]);

            $leadMailService->prepare($lead);

            return $lead;
        });

        $lead = $lead->fresh(['landingPage', 'property', 'valuation']);
        try {
            $leadSyncService->sync($lead);
        } catch (Throwable $e) {
            Log::error('lead sync failed before email delivery', [
                'lead_id' => $lead->id, 'exception_type' => get_class($e),
            ]);
        }
        // Each recipient has an independent durable delivery record.
        $leadMailService->sendForLead($lead);

        return redirect()
            ->route('landing-pages.results.show', [$landingPage, $lead])
            ->with('success', 'Vielen Dank! Ihre Bewertung wurde erstellt.');
    }
}
