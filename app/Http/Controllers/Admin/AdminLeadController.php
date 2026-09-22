<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\Pdf\ValuationReportPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminLeadController extends Controller
{
    public function edit(Lead $lead): Response
    {
        $lead->load(['landingPage', 'property', 'valuation', 'latestOnOfficeSync']);

        return Inertia::render('Admin/LeadEdit', [
            'lead' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'notes' => $lead->notes,
                'created_at' => $lead->created_at?->format('d.m.Y H:i'),
                'landing_page' => $lead->landingPage?->title,
                'landing_page_slug' => $lead->landingPage?->slug,
                'property' => [
                    'property_type' => $lead->property?->property_type,
                    'property_type_label' => $lead->property?->property_type_label,
                    'street' => $lead->property?->street,
                    'house_number' => $lead->property?->house_number,
                    'zip' => $lead->property?->zip,
                    'city' => $lead->property?->city,
                    'construction_year' => $lead->property?->construction_year,
                    'living_area' => $lead->property?->living_area,
                    'plot_area' => $lead->property?->plot_area,
                    'rooms' => $lead->property?->rooms,
                ],
                'onoffice' => [
                    'contact_id' => $lead->latestOnOfficeSync?->external_contact_id,
                    'estate_id' => $lead->latestOnOfficeSync?->external_estate_id,
                    'sync_status' => $lead->latestOnOfficeSync?->status,
                    'synced_at' => $lead->latestOnOfficeSync?->created_at?->format('d.m.Y H:i'),
                ],
                'valuation' => [
                    'estimated_value' => $lead->valuation?->estimated_value,
                    'range_low' => $lead->valuation?->range_low,
                    'range_high' => $lead->valuation?->range_high,
                    'range_percent' => $lead->valuation?->range_percent,
                    'provider' => $lead->valuation?->provider,
                    'source_label' => $lead->valuation?->source_label,
                    'status' => $lead->valuation?->status,
                    'updated_at' => $lead->valuation?->updated_at?->format('d.m.Y H:i'),
                ],
                'pdf_preview_url' => route('admin.leads.pdf.preview', $lead),
                'pdf_download_url' => route('admin.leads.pdf.download', $lead),
            ],
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'estimated_value' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'range_low' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'range_high' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ]);

        $estimated = round((float) $data['estimated_value'], 2);
        $low = round((float) $data['range_low'], 2);
        $high = round((float) $data['range_high'], 2);

        if ($low > $high) {
            throw ValidationException::withMessages([
                'range_low' => 'Der Mindestwert darf nicht über dem Höchstwert liegen.',
            ]);
        }

        if ($estimated < $low || $estimated > $high) {
            throw ValidationException::withMessages([
                'estimated_value' => 'Der Schätzwert muss innerhalb der Bewertungsspanne liegen.',
            ]);
        }

        $valuation = $lead->valuation()->firstOrNew();
        $previousProvider = $valuation->exists ? $valuation->provider : null;
        $previousResponse = is_array($valuation->provider_response) ? $valuation->provider_response : [];

        $lowerPercent = $estimated > 0 ? (($estimated - $low) / $estimated) * 100 : 0;
        $upperPercent = $estimated > 0 ? (($high - $estimated) / $estimated) * 100 : 0;
        $rangePercent = round(max($lowerPercent, $upperPercent), 2);

        $valuation->fill([
            'provider' => 'manual',
            'estimated_value' => $estimated,
            'range_percent' => $rangePercent,
            'range_low' => $low,
            'range_high' => $high,
            'status' => 'success',
            'error_message' => null,
            'provider_response' => array_merge($previousResponse, [
                'provider' => 'manual',
                'range_source' => 'manual',
                'manual_override' => [
                    'edited_at' => now()->toIso8601String(),
                    'edited_by' => $request->user()->id,
                    'previous_provider' => $previousProvider,
                ],
            ]),
        ])->save();

        return back()->with('success', 'Bewertung wurde gespeichert. Neue PDFs verwenden ab sofort diese Werte.');
    }

    public function preview(Lead $lead, ValuationReportPdfService $pdfService): HttpResponse
    {
        return $pdfService->stream($lead);
    }

    public function download(Lead $lead, ValuationReportPdfService $pdfService): HttpResponse
    {
        return $pdfService->download($lead);
    }
}
