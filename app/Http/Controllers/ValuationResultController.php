<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Lead;
use App\Services\LandingPages\TemplateRenderer;
use App\Support\LeadValuationViewData;
use Inertia\Inertia;
use Inertia\Response;

class ValuationResultController extends Controller
{
    public function show(
        LandingPage $landingPage,
        Lead $lead,
        TemplateRenderer $renderer,
        LeadValuationViewData $viewData,
    ): Response {
        abort_unless($landingPage->published_at, 404);
        abort_unless((int) $lead->landing_page_id === (int) $landingPage->id, 404);

        return Inertia::render('LandingPages/Show', [
            'page' => $renderer->renderPayload($landingPage),
            ...$viewData->make($lead),
        ]);
    }
}
