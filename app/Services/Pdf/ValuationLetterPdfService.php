<?php

namespace App\Services\Pdf;

use App\Models\Lead;
use App\Models\ValuationLetter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ValuationLetterPdfService
{
    public function bytes(Lead $lead, ValuationLetter $letter): string
    {
        $lead->loadMissing('property');

        $photos = [];
        $disk = Storage::disk('local');
        foreach (range(1, 4) as $slot) {
            $path = data_get($letter->photo_paths, (string) $slot);
            if (! is_string($path) || ! $disk->exists($path)) {
                continue;
            }
            // Photos stay on the private local disk. Dompdf reads them inside
            // the application chroot; no public URL and no data-URI conversion.
            $photos[$slot] = $disk->path($path);
        }

        // Use the original 2 COME HOME PNG already stored in the repository.
        // It is identical to the supplied logo, rather than the old traced SVG.
        $brand = [
            'logo' => public_path('images/logo-2comehome.png'),
            'watermark' => resource_path('valuation-letter/brand/watermark.svg'),
        ];

        $livingArea = (float) ($lead->property?->living_area ?? 0);
        $pricePerSquareMeter = $livingArea > 0 ? [
            'low' => (int) round((float) $letter->value_low / $livingArea, -1),
            'high' => (int) round((float) $letter->value_high / $livingArea, -1),
        ] : null;

        return Pdf::loadView('pdf.valuation-letter', [
            'lead' => $lead,
            'letter' => $letter,
            'brand' => $brand,
            'company' => config('valuation_letter'),
            'photos' => $photos,
            'pricePerSquareMeter' => $pricePerSquareMeter,
        ])
            ->setPaper('a4')
            ->setOption('chroot', base_path())
            ->setOption('isRemoteEnabled', false)
            ->output();
    }

    public function stream(Lead $lead, ValuationLetter $letter): Response
    {
        return response($this->bytes($lead, $letter), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->filename($lead).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Lead $lead, ValuationLetter $letter): Response
    {
        return response($this->bytes($lead, $letter), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($lead).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function filename(Lead $lead): string
    {
        return 'Immobilienbewertung-'.$lead->uuid.'.pdf';
    }
}
