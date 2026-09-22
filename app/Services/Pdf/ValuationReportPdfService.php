<?php

namespace App\Services\Pdf;

use App\Models\Lead;
use App\Services\Maps\GoogleStaticMapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ValuationReportPdfService
{
    public const TYPE_INITIAL = 'initial';

    public const TYPE_FINAL = 'final';

    public function __construct(private readonly GoogleStaticMapService $mapService) {}

    public function bytes(Lead $lead, string $type = self::TYPE_INITIAL): string
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        return Pdf::loadView('pdf.valuation-report', $this->viewData($lead, $type))
            ->setPaper('a4')
            ->output();
    }

    public function stream(Lead $lead, string $type = self::TYPE_INITIAL): Response
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $pdf = Pdf::loadView('pdf.valuation-report', $this->viewData($lead, $type))->setPaper('a4');

        return $pdf->stream($this->filename($lead, $type));
    }

    public function download(Lead $lead, string $type = self::TYPE_INITIAL): Response
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $pdf = Pdf::loadView('pdf.valuation-report', $this->viewData($lead, $type))->setPaper('a4');

        return $pdf->download($this->filename($lead, $type));
    }

    /** @return array<string, mixed> */
    private function viewData(Lead $lead, string $type): array
    {
        $type = $type === self::TYPE_FINAL ? self::TYPE_FINAL : self::TYPE_INITIAL;

        return [
            'lead' => $lead,
            'property' => $lead->property,
            'valuation' => $lead->valuation,
            'mapImage' => $this->mapService->forProperty($lead->property),
            'reportType' => $type,
            'isFinalReport' => $type === self::TYPE_FINAL,
        ];
    }

    private function filename(Lead $lead, string $type): string
    {
        return $type === self::TYPE_FINAL
            ? 'abschliessende-wertermittlung-'.$lead->uuid.'.pdf'
            : 'immobilien-ersteinschaetzung-'.$lead->uuid.'.pdf';
    }
}
