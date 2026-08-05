<?php

namespace App\Support;

use App\Models\Lead;

class LeadValuationViewData
{
    /**
     * Build the safe presentation payload shared by the redirect and result page.
     *
     * @return array{valuation: array<string, mixed>, lead_summary: array<string, mixed>, report_url: string}
     */
    public function make(Lead $lead): array
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        return [
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
        ];
    }
}
