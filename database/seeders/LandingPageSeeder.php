<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    /**
     * Seed the landing pages used for the PriceHubble valuation funnel.
     */
    public function run(): void
    {
        $template = LandingPageTemplate::query()->updateOrCreate(
            ['key' => 'valuation-standard'],
            [
                'name' => 'Immobilienbewertung Standard',
                'schema' => [
                    'fields' => ['eyebrow', 'hero_title', 'hero_text', 'target_audience', 'keywords'],
                ],
                'default_content' => [
                    'eyebrow' => 'Immobilienbewertung',
                    'hero_title' => 'Erfahren Sie den Wert Ihrer Immobilie',
                    'hero_text' => 'In nur wenigen Schritten erhalten Sie eine fundierte, unverbindliche Markteinschätzung.',
                ],
                'is_active' => true,
            ]
        );

        foreach ($this->pages() as $page) {
            LandingPage::query()->updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'landing_page_template_id' => $template->id,
                    'campaign_id' => null,
                    'title' => $page['title'],
                    'description' => $page['description'],
                    'content' => [
                        'eyebrow' => $page['eyebrow'],
                        'hero_title' => $page['hero_title'],
                        'hero_text' => $page['hero_text'],
                        'target_audience' => $page['target_audience'],
                        'keywords' => $page['keywords'],
                    ],
                    'seo' => [
                        'title' => $page['title'] . ' | 2 COME HOME',
                        'description' => $page['hero_text'],
                        'keywords' => implode(', ', $page['keywords']),
                    ],
                    'tracking_overrides' => null,
                    'valuation_range_percent' => null,
                    'published_at' => now(),
                ]
            );
        }
    }

    /**
     * @return array<int, array{
     *     slug: string,
     *     title: string,
     *     description: string,
     *     eyebrow: string,
     *     hero_title: string,
     *     hero_text: string,
     *     target_audience: string,
     *     keywords: array<int, string>,
     * }>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'bestandsimmobilie-verkaufen',
                'title' => 'Bestandsimmobilie erfolgreich verkaufen',
                'description' => 'Fundierte Markteinschätzung und persönliche Begleitung für Eigentümer mit konkreter Verkaufsabsicht.',
                'eyebrow' => 'Immobilienverkauf',
                'hero_title' => 'Verkaufen Sie Ihre Bestandsimmobilie erfolgreich und ohne Stress',
                'hero_text' => 'Sie haben sich entschieden, Ihre Immobilie zu verkaufen? Wir ermitteln in wenigen Minuten eine fundierte Markteinschätzung und begleiten Sie kompetent bis zum erfolgreichen Verkaufsabschluss.',
                'target_audience' => 'Eigentümer mit konkreter Verkaufsabsicht',
                'keywords' => ['Haus verkaufen', 'Wohnung verkaufen', 'Immobilie verkaufen'],
            ],
            [
                'slug' => 'immobilie-kostenlos-bewerten',
                'title' => 'Immobilie kostenlos bewerten lassen',
                'description' => 'Kostenlose und unverbindliche Ersteinschätzung des Marktwerts für Eigentümer, die sich noch nicht sicher sind.',
                'eyebrow' => 'Kostenlose Immobilienbewertung',
                'hero_title' => 'Was ist Ihre Immobilie wirklich wert?',
                'hero_text' => 'Erhalten Sie kostenlos und unverbindlich eine fundierte Ersteinschätzung des aktuellen Marktwerts Ihrer Immobilie – in nur wenigen Minuten.',
                'target_audience' => 'Eigentümer, die erst den Marktwert wissen möchten',
                'keywords' => ['Immobilienbewertung', 'Hauswert berechnen', 'Wohnungswert'],
            ],
            [
                'slug' => 'erbimmobilie-verkaufen',
                'title' => 'Erbimmobilie verkaufen',
                'description' => 'Sichere und faire Begleitung beim Verkauf einer geerbten Immobilie für Erbengemeinschaften und Alleinerben.',
                'eyebrow' => 'Erbimmobilie',
                'hero_title' => 'Erbimmobilie verkaufen – sicher, fair und ohne Streit',
                'hero_text' => 'Ob als Erbengemeinschaft oder Alleinerbe: Wir unterstützen Sie beim Verkauf der geerbten Immobilie und sorgen für eine faire, marktgerechte Bewertung.',
                'target_audience' => 'Erbengemeinschaften und Alleinerben',
                'keywords' => ['Erbimmobilie verkaufen', 'geerbtes Haus verkaufen'],
            ],
            [
                'slug' => 'scheidungsimmobilie-verkaufen',
                'title' => 'Scheidungsimmobilie verkaufen',
                'description' => 'Neutrale Marktwertermittlung und einfühlsame Begleitung für Eigentümer in Trennung oder Scheidung.',
                'eyebrow' => 'Immobilie bei Scheidung',
                'hero_title' => 'Immobilie bei Trennung oder Scheidung fair verkaufen',
                'hero_text' => 'Eine Trennung ist schon schwer genug – wir sorgen für eine neutrale, marktgerechte Bewertung Ihrer gemeinsamen Immobilie und einen reibungslosen Verkaufsprozess.',
                'target_audience' => 'Eigentümer in Trennung oder Scheidung',
                'keywords' => ['Haus bei Scheidung verkaufen', 'Immobilie trennen'],
            ],
            [
                'slug' => 'kapitalanlage-verkaufen',
                'title' => 'Kapitalanlage verkaufen',
                'description' => 'Realistische Marktwertermittlung für Eigentümer vermieteter Wohnungen und Mehrfamilienhäuser.',
                'eyebrow' => 'Kapitalanlage-Immobilie',
                'hero_title' => 'Vermietete Immobilie oder Mehrfamilienhaus gewinnbringend verkaufen',
                'hero_text' => 'Wir kennen den Investmentmarkt und ermitteln den realistischen Marktwert Ihrer vermieteten Wohnung oder Ihres Mehrfamilienhauses – für einen erfolgreichen Verkauf an Kapitalanleger.',
                'target_audience' => 'Eigentümer vermieteter Wohnungen und Mehrfamilienhäuser',
                'keywords' => ['Kapitalanlage verkaufen', 'vermietete Wohnung verkaufen'],
            ],
        ];
    }
}
