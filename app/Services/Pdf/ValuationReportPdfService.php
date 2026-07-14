<?php

namespace App\Services\Pdf;

use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;

class ValuationReportPdfService
{
    public function stream(Lead $lead)
    {
        $lead->loadMissing(['landingPage', 'property', 'valuation']);

        return Pdf::loadView('pdf.valuation-report', [
            'lead' => $lead,
            'property' => $lead->property,
            'valuation' => $lead->valuation,
        ])->stream('immobilienbewertung-' . $lead->uuid . '.pdf');
    }
}
