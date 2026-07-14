<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Pdf\ValuationReportPdfService;

class ValuationReportController extends Controller
{
    public function show(Lead $lead, ValuationReportPdfService $pdfService)
    {
        return $pdfService->stream($lead);
    }
}
