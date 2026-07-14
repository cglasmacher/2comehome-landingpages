<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Services\LandingPages\TemplateRenderer;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function show(LandingPage $landingPage, TemplateRenderer $renderer): Response
    {
        abort_unless($landingPage->published_at, 404);

        return Inertia::render('LandingPages/Show', [
            'page' => $renderer->renderPayload($landingPage),
        ]);
    }
}
