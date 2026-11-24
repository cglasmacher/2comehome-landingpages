<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveValuationLetterRequest;
use App\Models\Lead;
use App\Services\Pdf\ValuationLetterPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class AdminValuationLetterController extends Controller
{
    public function edit(Lead $lead): Response
    {
        $lead->load(['property', 'valuation', 'valuationLetter']);
        $letter = $lead->valuationLetter;
        $needsReview = $letter && $lead->property?->updated_at?->gt($letter->updated_at);
        $data = $letter ? [
            ...$letter->only([
                'recipient_name', 'adviser_name', 'adviser_role', 'adviser_mobile', 'adviser_email',
                'property_label', 'floor_location', 'room_summary', 'object_description',
                'market_description', 'value_low', 'value_high', 'orientation_value', 'valuation_notes',
            ]),
            'issued_on' => $letter->issued_on->format('Y-m-d'),
            'valuation_date' => $letter->valuation_date->format('Y-m-d'),
        ] : $this->defaults($lead);

        $photos = [];
        foreach (range(1, 4) as $slot) {
            if (data_get($letter?->photo_paths, (string) $slot)) {
                $photos[$slot] = route('admin.leads.letter.photo', [$lead, $slot]);
            }
        }

        return Inertia::render('Admin/ValuationLetterEdit', [
            'lead' => [
                'id' => $lead->id,
                'name' => trim($lead->first_name.' '.$lead->last_name),
                'address' => trim(implode(' ', array_filter([
                    $lead->property?->street, $lead->property?->house_number,
                    $lead->property?->zip, $lead->property?->city,
                ]))),
                'living_area' => $lead->property?->living_area,
                'source_status' => $lead->valuation?->status,
                'edit_url' => route('admin.leads.edit', $lead),
            ],
            'fields' => $data,
            'saved' => (bool) $letter,
            'needs_review' => (bool) $needsReview,
            'photos' => $photos,
            'preview_url' => route('admin.leads.letter.preview', $lead),
            'download_url' => route('admin.leads.letter.download', $lead),
        ]);
    }

    public function store(SaveValuationLetterRequest $request, Lead $lead): RedirectResponse
    {
        $values = $request->validated();
        $letter = $lead->valuationLetter()->first();
        $paths = $letter?->photo_paths ?? [];
        $createdPaths = [];
        $oldPaths = [];

        try {
            for ($slot = 1; $slot <= 4; $slot++) {
                $key = (string) $slot;
            $old = $paths[$key] ?? null;
            $remove = $request->boolean('remove_photo_'.$slot);
            unset($values['remove_photo_'.$slot], $values['photo_'.$slot]);

            if ($request->hasFile('photo_'.$slot)) {
                // New upload takes precedence over an existing-photo deletion.
                $path = $this->storePhoto($lead, $slot, $request->file('photo_'.$slot));
                $createdPaths[] = $path;
                $paths[$key] = $path;
                if ($old) {
                    $oldPaths[] = $old;
                }
            } elseif ($remove && $old) {
                $oldPaths[] = $old;
                unset($paths[$key]);
            }
            }

            DB::transaction(function () use ($lead, $values, $paths, $request): void {
                $lead->valuationLetter()->updateOrCreate([], [
                    ...$values,
                    'updated_by' => $request->user()->id,
                    'photo_paths' => $paths,
                ]);
            });
        } catch (Throwable $error) {
            if ($createdPaths !== []) {
                Storage::disk('local')->delete($createdPaths);
            }
            throw $error;
        }

        if ($oldPaths !== []) {
            Storage::disk('local')->delete($oldPaths);
        }

        return redirect()->route('admin.leads.letter.edit', $lead)
            ->with('success', 'Bewertungsanschreiben gespeichert. Vorschau und Download stehen bereit.');
    }

    public function preview(Lead $lead, ValuationLetterPdfService $pdf): HttpResponse
    {
        $letter = $lead->valuationLetter;
        abort_unless($letter, 404);
        abort_if($lead->property?->updated_at?->gt($letter->updated_at), 409, 'Objektdaten wurden nach dem Anschreiben geändert. Bitte prüfen und speichern Sie das Anschreiben erneut.');

        return $pdf->stream($lead, $letter);
    }

    public function download(Lead $lead, ValuationLetterPdfService $pdf): HttpResponse
    {
        $letter = $lead->valuationLetter;
        abort_unless($letter, 404);
        abort_if($lead->property?->updated_at?->gt($letter->updated_at), 409, 'Objektdaten wurden nach dem Anschreiben geändert. Bitte prüfen und speichern Sie das Anschreiben erneut.');

        return $pdf->download($lead, $letter);
    }

    public function photo(Lead $lead, int $slot): HttpResponse
    {
        abort_unless($slot >= 1 && $slot <= 4, 404);
        $path = data_get($lead->valuationLetter?->photo_paths, (string) $slot);
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function defaults(Lead $lead): array
    {
        $property = $lead->property;
        $valuation = $lead->valuation;
        $type = $property?->property_type_label ?: 'Immobilie';
        $type = $property?->property_type === 'wohnung' ? 'Eigentumswohnung' : $type;
        $address = trim(implode(' ', array_filter([$property?->street, $property?->house_number])));
        $place = trim(implode(' ', array_filter([$property?->zip, $property?->city])));
        $location = implode(', ', array_filter([$address, $place]));
        $area = $property?->living_area ? 'ca. '.$property->living_area.' m²' : 'nicht abschließend ermittelt';

        $currentValuation = $valuation && ! in_array($valuation->status, ['stale', 'failed'], true);
        $low = $currentValuation ? $valuation->range_low : null;
        $high = $currentValuation ? $valuation->range_high : null;
        $orient = $currentValuation ? $valuation->estimated_value : null;

        return [
            'recipient_name' => trim($lead->first_name.' '.$lead->last_name) ?: 'Bitte Empfänger eintragen',
            'adviser_name' => config('valuation_letter.adviser_name'),
            'adviser_role' => config('valuation_letter.adviser_role'),
            'adviser_mobile' => config('valuation_letter.adviser_mobile'),
            'adviser_email' => config('valuation_letter.adviser_email'),
            'issued_on' => now('Europe/Berlin')->toDateString(),
            'property_label' => $type,
            'floor_location' => '',
            'room_summary' => $property?->rooms ? $property->rooms.' Zimmer' : '',
            'object_description' => 'Bei der zu bewertenden Immobilie handelt es sich um eine '. $type
                .($location !== '' ? ' in '.$location : '').'. Die angegebene Wohnfläche beträgt '.$area.'.'
                ."\n\n".'Zur weiteren Einordnung sind insbesondere Zustand, Ausstattungsqualität und objektspezifische Besonderheiten zu berücksichtigen.',
            'market_description' => 'Unter Berücksichtigung der Wohnfläche, der Objektart, der Lage und des örtlichen Immobilienmarktes wird für die Immobilie folgende marktbezogene Preisspanne eingeschätzt:',
            'value_low' => $low ?? '',
            'value_high' => $high ?? '',
            'orientation_value' => $orient ?? ($low !== null && $high !== null ? round(((float) $low + (float) $high) / 2, 0) : ''),
            'valuation_notes' => 'Der tatsächlich erzielbare Kaufpreis kann insbesondere durch den konkreten Zustand und Modernisierungsgrad der Immobilie, die Ausstattung, den energetischen Zustand des Gebäudes sowie weitere objektspezifische Besonderheiten beeinflusst werden.',
            'valuation_date' => now('Europe/Berlin')->toDateString(),
        ];
    }

    private function storePhoto(Lead $lead, int $slot, UploadedFile $file): string
    {
        $sourceBytes = file_get_contents($file->getRealPath());
        $source = $sourceBytes !== false ? @imagecreatefromstring($sourceBytes) : false;
        if (! $source) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'photo_'.$slot => 'Das Foto konnte nicht gelesen werden.',
            ]);
        }

        // Mirror the original four-photo composition, normalizing the output
        // and stripping EXIF metadata before storing it privately.
        $targetWidth = $slot === 1 ? 600 : 1000;
        $targetHeight = $slot === 1 ? 800 : 750;
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $cropWidth = min($sourceWidth, (int) round($targetWidth / $scale));
        $cropHeight = min($sourceHeight, (int) round($targetHeight / $scale));

        $image = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $image, $source, 0, 0,
            (int) floor(($sourceWidth - $cropWidth) / 2),
            (int) floor(($sourceHeight - $cropHeight) / 2),
            $targetWidth, $targetHeight, $cropWidth, $cropHeight,
        );

        ob_start();
        imagejpeg($image, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($image);

        if (! is_string($jpeg)) {
            throw new \RuntimeException('Bild konnte nicht verarbeitet werden.');
        }

        $path = 'valuation-letters/'.$lead->uuid.'/photo-'.$slot.'-'.Str::uuid().'.jpg';
        throw_unless(Storage::disk('local')->put($path, $jpeg), \RuntimeException::class, 'Foto konnte nicht gespeichert werden.');

        return $path;
    }
}
