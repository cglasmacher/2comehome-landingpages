<?php

namespace App\Services\LandingPages;

use App\Models\LandingPage;

class TemplateRenderer
{
    public function renderPayload(LandingPage $landingPage): array
    {
        $landingPage->loadMissing(['campaign', 'template']);

        return [
            'id' => $landingPage->id,
            'slug' => $landingPage->slug,
            'title' => $landingPage->title,
            'description' => $landingPage->description,
            'content' => $landingPage->content,
            'seo' => $landingPage->seo,
            'template_key' => $landingPage->template->key,
            'tracking' => array_replace_recursive(
                $landingPage->campaign?->tracking_settings ?? [],
                $landingPage->tracking_overrides ?? []
            ),
            'calendly_url' => config('landingpages.calendly_url'),
            'valuation_range_percent' => $landingPage->valuation_range_percent ?: config('landingpages.default_range_percent'),
        ];
    }
}
