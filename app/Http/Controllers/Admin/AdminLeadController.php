<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLeadPropertyRequest;
use App\Models\Lead;
use App\Services\Pdf\ValuationReportPdfService;
use App\Services\Valuation\ValuationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminLeadController extends Controller
{
    public function edit(Lead $lead): Response
    {
        $lead->load(['landingPage', 'property', 'valuation', 'latestOnOfficeSync', 'valuationLetter']);

        return Inertia::render('Admin/LeadEdit', [
            'property_types' => collect(config('landingpages.property_types', []))
                ->filter(fn (array $type, string $key) => ($type['selectable'] ?? true) === true
                    || $key === $lead->property?->property_type)
                ->map(fn (array $type, string $key) => ['value' => $key, 'label' => $type['label'] ?? ucfirst($key)])
                ->values()
                ->all(),
            'contracts' => $lead->brokerageContracts()->latest()->get()->map(fn ($contract) => [
                'id' => $contract->id, 'status' => $contract->status,
                'offer_price' => $contract->offer_price,
                'created_at' => $contract->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i'),
                'edit_url' => route('admin.contracts.edit', $contract),
            ]),
            'lead' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'letter_exists' => (bool) $lead->valuationLetter,
                'letter_needs_review' => (bool) ($lead->valuationLetter && (
                    $lead->property?->updated_at?->gt($lead->valuationLetter->updated_at)
                    || blank($lead->valuationLetter->recipient_street)
                    || blank($lead->valuationLetter->recipient_zip)
                    || blank($lead->valuationLetter->recipient_city)
                )),
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
                    'number_of_units' => $lead->property?->number_of_units,
                ],
                'onoffice' => [
                    'kdnr' => $lead->latestOnOfficeSync?->onoffice_kdnr,
                    'immonr' => $lead->latestOnOfficeSync?->onoffice_immonr,
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
                'final_pdf_preview_url' => route('admin.leads.final-pdf.preview', $lead),
                'final_pdf_download_url' => route('admin.leads.final-pdf.download', $lead),
            ],
        ]);
    }

    /**
     * Update only the submitted property attributes. Existing valuations are not
     * silently reused against changed inputs, and previously sent e-mails remain untouched.
     */
    public function updateProperty(UpdateLeadPropertyRequest $request, Lead $lead, ValuationService $valuationService): RedirectResponse
    {
        $data = $request->validated();
        $recalculate = (bool) ($data['recalculate'] ?? false);
        unset($data['recalculate']);

        // Keep the stored payload aligned with the public valuation form.
        if ($data['property_type'] === 'wohnung') {
            $data['plot_area'] = null;
        }
        if ($data['property_type'] !== 'mehrfamilienhaus') {
            $data['number_of_units'] = null;
        }

        $fields = [
            'property_type', 'street', 'house_number', 'zip', 'city', 'country',
            'construction_year', 'living_area', 'plot_area', 'rooms', 'number_of_units',
        ];
        $values = array_replace(array_fill_keys($fields, null), $data);
        $property = $lead->property()->firstOrNew([]);
        $property->fill($values);

        $hasChanges = ! $property->exists || $property->isDirty();
        $changedFields = array_keys($property->getDirty());

        if ($hasChanges) {
            DB::transaction(function () use ($lead, $property, $request, $changedFields): void {
                $property->save();
                $valuation = $lead->valuation()->first();

                if ($valuation) {
                    $response = is_array($valuation->provider_response) ? $valuation->provider_response : [];
                    $response['property_edit'] = [
                        'changed_at' => now()->toIso8601String(),
                        'changed_by' => $request->user()->id,
                        'changed_fields' => $changedFields,
                        'previous_status' => $valuation->status,
                    ];
                    $valuation->update([
                        'status' => 'stale',
                        'provider_response' => $response,
                        'error_message' => 'Die Objektdaten wurden geändert. Bewertung muss aktualisiert werden.',
                    ]);
                }
            });

            Log::info('admin lead property updated', [
                'lead_id' => $lead->id,
                'user_id' => $request->user()->id,
                'changed_fields' => $changedFields,
            ]);
        }

        if ($recalculate) {
            // An explicit action only: external calls can cost credits and overwrite manual valuations.
            $lead = $lead->fresh(['property', 'landingPage']);
            $rangePercent = (float) ($lead->landingPage?->valuation_range_percent
                ?: config('landingpages.default_range_percent', 7.5));
            $result = $valuationService->createForLead($lead, $rangePercent);

            if ($result->status !== 'completed') {
                // Do not replace a previously saved value with an unsuccessful provider result.
                return back()->with('warning', 'Objektdaten gespeichert. Eine neue Bewertung war nicht möglich. Bitte den Schätzwert manuell prüfen und speichern.');
            }

            $lead->valuation()->updateOrCreate([], [
                'provider' => $result->provider,
                'estimated_value' => $result->estimatedValue,
                'range_percent' => $result->rangePercent,
                'range_low' => $result->rangeLow,
                'range_high' => $result->rangeHigh,
                'status' => $result->status,
                'provider_payload' => null,
                'provider_response' => array_merge($result->rawResponse, [
                    'admin_recalculation' => [
                        'by' => $request->user()->id,
                        'at' => now()->toIso8601String(),
                    ],
                ]),
                'error_message' => null,
            ]);

            return back()->with('success', 'Objektdaten gespeichert und Bewertung neu berechnet. PDFs verwenden ab sofort die aktuellen Werte.');
        }

        return back()->with(
            $hasChanges ? 'warning' : 'success',
            $hasChanges
                ? 'Objektdaten gespeichert. Die bisherige Bewertung ist als veraltet markiert. Bitte die Bewertung manuell aktualisieren oder neu berechnen. onOffice wird nicht automatisch synchronisiert.'
                : 'Keine Änderungen an den Objektdaten.',
        );
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

        $valuation = $lead->valuation()->firstOrNew();
        $previousProvider = $valuation->exists ? $valuation->provider : null;
        $previousResponse = is_array($valuation->provider_response) ? $valuation->provider_response : [];

        $valuation->fill([
            'provider' => 'manual',
            'estimated_value' => $estimated,
            'range_percent' => 0,
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

    public function finalPreview(Lead $lead, ValuationReportPdfService $pdfService): HttpResponse
    {
        return $pdfService->stream($lead, ValuationReportPdfService::TYPE_FINAL);
    }

    public function finalDownload(Lead $lead, ValuationReportPdfService $pdfService): HttpResponse
    {
        return $pdfService->download($lead, ValuationReportPdfService::TYPE_FINAL);
    }
}
