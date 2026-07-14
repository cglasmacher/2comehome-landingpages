<?php

namespace App\Services\Pdf;

use App\Models\Lead;
use Illuminate\Http\Response;

class ValuationReportPdfService
{
    public function stream(Lead $lead): Response
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        return response()->view('pdf.valuation-report', [
            'lead' => $lead,
            'property' => $lead->property,
            'valuation' => $lead->valuation,
        ], 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="bewertungsbericht-' . $lead->uuid . '.html"',
        ]);
    }
}
