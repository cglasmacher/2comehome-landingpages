<?php

namespace App\Services\Pdf;

use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ValuationReportPdfService
{
    public function stream(Lead $lead): Response
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $pdf = Pdf::loadView('pdf.valuation-report', [
            'lead' => $lead,
            'property' => $lead->property,
            'valuation' => $lead->valuation,
        ])->setPaper('a4');

        return $pdf->stream('bewertungsbericht-' . $lead->uuid . '.pdf');
    }

    public function download(Lead $lead): Response
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        $pdf = Pdf::loadView('pdf.valuation-report', [
            'lead' => $lead,
            'property' => $lead->property,
            'valuation' => $lead->valuation,
        ])->setPaper('a4');

        return $pdf->download('bewertungsbericht-' . $lead->uuid . '.pdf');
    }
}
