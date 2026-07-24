<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Mail\LeadValuationSummaryMail;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\Valuation;
use App\Services\OnOffice\LeadSyncService;
use App\Services\PriceHubble\ValuationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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

        $lead = $lead->fresh(['landingPage', 'property', 'valuation']);
        $leadSyncService->sync($lead);

        try {
            Mail::to($lead->email)->send(new LeadValuationSummaryMail($lead));

            Log::info('lead valuation summary email sent', [
                'lead_id' => $lead->id,
                'recipient' => $lead->email,
            ]);
        } catch (Throwable $e) {
            report($e);

            Log::error('lead valuation summary email failed', [
                'lead_id' => $lead->id,
                'recipient' => $lead->email,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with([
            'success' => 'Vielen Dank! Ihre Bewertung wurde erstellt.',
            'valuation' => [
                'estimated_value' => $lead->valuation?->estimated_value,
                'range_percent' => $lead->valuation?->range_percent,
                'range_low' => $lead->valuation?->range_low,
                'range_high' => $lead->valuation?->range_high,
                'status' => $lead->valuation?->status,
            ],
            'lead_summary' => [
                'contact' => [
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                ],
                'property' => [
                    'property_type' => $lead->property?->property_type,
                    'street' => $lead->property?->street,
                    'house_number' => $lead->property?->house_number,
                    'zip' => $lead->property?->zip,
                    'city' => $lead->property?->city,
                    'construction_year' => $lead->property?->construction_year,
                    'living_area' => $lead->property?->living_area,
                    'plot_area' => $lead->property?->plot_area,
                    'rooms' => $lead->property?->rooms,
                ],
                'notes' => $lead->notes,
            ],
            'report_url' => route('valuation-reports.show', $lead),
        ]);
    }
}
