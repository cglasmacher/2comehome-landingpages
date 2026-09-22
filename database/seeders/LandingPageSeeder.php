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

            // Hohe Verkaufsintention: konkrete Problemsituationen und verkaufsnahe Suchanfragen.
            [
                'slug' => 'immobilie-ohne-makler-verkaufen',
                'title' => 'Immobilie ohne Makler verkaufen',
                'description' => 'Ersteinschätzung und Orientierung für Eigentümer, die ihre Immobilie zunächst privat verkaufen möchten.',
                'eyebrow' => 'Privatverkauf vorbereiten',
                'hero_title' => 'Immobilie ohne Makler verkaufen? Starten Sie mit einer realistischen Einschätzung.',
                'hero_text' => 'Sie möchten Ihre Immobilie selbst verkaufen? Bevor Sie einen Angebotspreis festlegen, erhalten Sie bei uns eine fundierte Ersteinschätzung und einen klaren Überblick über die nächsten Schritte.',
                'target_audience' => 'Eigentümer, die einen privaten Immobilienverkauf vorbereiten',
                'keywords' => ['Immobilie ohne Makler verkaufen', 'Haus privat verkaufen', 'Wohnung privat verkaufen'],
            ],
            [
                'slug' => 'immobilie-im-alter-verkaufen',
                'title' => 'Immobilie im Alter verkaufen',
                'description' => 'Persönliche Immobilienbewertung und Verkaufsbegleitung für Eigentümer, die ihre Wohnsituation im Alter verändern möchten.',
                'eyebrow' => 'Immobilie im Alter',
                'hero_title' => 'Das eigene Zuhause verkaufen – gut vorbereitet in den nächsten Lebensabschnitt',
                'hero_text' => 'Wenn das Haus zu groß wird oder sich die Lebenssituation verändert, unterstützen wir Sie mit einer realistischen Einschätzung, persönlicher Beratung und einem strukturierten Verkaufsprozess.',
                'target_audience' => 'Ältere Eigentümer und Familien, die einen Immobilienverkauf vorbereiten',
                'keywords' => ['Haus verkaufen im Alter', 'Immobilie im Alter verkaufen', 'Haus verkaufen und kleiner wohnen'],
            ],
            [
                'slug' => 'immobilie-pflegeheim-verkaufen',
                'title' => 'Immobilie wegen Pflegeheim verkaufen',
                'description' => 'Strukturierte Unterstützung für Eigentümer und Angehörige beim Immobilienverkauf im Zusammenhang mit einem Pflegeheim.',
                'eyebrow' => 'Immobilie & Pflege',
                'hero_title' => 'Wenn ein Umzug ins Pflegeheim ansteht, schaffen wir Klarheit bei der Immobilie',
                'hero_text' => 'Wir unterstützen Eigentümer und Angehörige bei Bewertung, Unterlagen und Verkauf der Immobilie – persönlich, nachvollziehbar und mit Blick auf die besondere Situation.',
                'target_audience' => 'Eigentümer und Angehörige bei einem bevorstehenden oder erfolgten Umzug ins Pflegeheim',
                'keywords' => ['Haus verkaufen Pflegeheim', 'Immobilie Pflegeheim verkaufen', 'Hausverkauf Pflegekosten'],
            ],
            [
                'slug' => 'immobilie-schnell-verkaufen',
                'title' => 'Immobilie schnell verkaufen',
                'description' => 'Schnelle Ersteinschätzung und strukturierter Verkaufsstart für Eigentümer mit zeitkritischem Verkaufswunsch.',
                'eyebrow' => 'Schneller Immobilienverkauf',
                'hero_title' => 'Ihre Immobilie soll zeitnah verkauft werden? Beginnen wir mit den richtigen Zahlen.',
                'hero_text' => 'Wenn Zeit eine wichtige Rolle spielt, braucht es einen realistischen Preis und einen klaren Ablauf. Wir schaffen schnell eine belastbare Grundlage für die nächsten Verkaufsschritte.',
                'target_audience' => 'Eigentümer mit kurzfristigem oder zeitkritischem Verkaufswunsch',
                'keywords' => ['Immobilie schnell verkaufen', 'Haus schnell verkaufen', 'Wohnung schnell verkaufen'],
            ],
            [
                'slug' => 'sanierungsbeduerftige-immobilie-verkaufen',
                'title' => 'Sanierungsbedürftige Immobilie verkaufen',
                'description' => 'Markteinschätzung für Eigentümer, die vor der Frage stehen, ob sich eine Sanierung vor dem Verkauf noch lohnt.',
                'eyebrow' => 'Sanieren oder verkaufen?',
                'hero_title' => 'Erst sanieren oder direkt verkaufen?',
                'hero_text' => 'Nicht jede Modernisierung zahlt sich vor einem Verkauf aus. Wir ordnen Ihre Immobilie realistisch ein und zeigen Ihnen, welche Ausgangslage Sie für den Verkauf haben.',
                'target_audience' => 'Eigentümer sanierungs- oder renovierungsbedürftiger Immobilien',
                'keywords' => ['sanierungsbedürftiges Haus verkaufen', 'Haus unsaniert verkaufen', 'erst sanieren oder verkaufen'],
            ],
            [
                'slug' => 'immobilie-mit-restschuld-verkaufen',
                'title' => 'Immobilie mit Restschuld verkaufen',
                'description' => 'Ersteinschätzung für Eigentümer, die ihre Immobilie trotz laufender Finanzierung oder Restschuld verkaufen möchten.',
                'eyebrow' => 'Verkauf trotz Finanzierung',
                'hero_title' => 'Haus verkaufen, obwohl der Kredit noch läuft?',
                'hero_text' => 'Eine laufende Finanzierung schließt einen Verkauf nicht automatisch aus. Wir ermitteln zunächst den aktuellen Immobilienwert und schaffen damit eine belastbare Grundlage für Ihre weiteren Schritte.',
                'target_audience' => 'Eigentümer mit laufender Immobilienfinanzierung oder Restschuld',
                'keywords' => ['Haus verkaufen trotz Kredit', 'Immobilie mit Restschuld verkaufen', 'Haus verkaufen Finanzierung läuft'],
            ],
            [
                'slug' => 'vermietete-wohnung-verkaufen',
                'title' => 'Vermietete Wohnung verkaufen',
                'description' => 'Markteinschätzung und Verkaufsstrategie speziell für Eigentümer vermieteter Eigentumswohnungen.',
                'eyebrow' => 'Vermietete Eigentumswohnung',
                'hero_title' => 'Vermietete Wohnung verkaufen – mit einer realistischen Investment-Einwertung',
                'hero_text' => 'Bei vermieteten Wohnungen zählen nicht nur Lage und Ausstattung, sondern auch Miete, Mietverhältnis und Rendite. Wir ordnen Ihre Wohnung marktgerecht für den Verkauf ein.',
                'target_audience' => 'Eigentümer vermieteter Eigentumswohnungen',
                'keywords' => ['vermietete Wohnung verkaufen', 'Eigentumswohnung vermietet verkaufen', 'Wohnung an Kapitalanleger verkaufen'],
            ],
            [
                'slug' => 'mehrfamilienhaus-verkaufen',
                'title' => 'Mehrfamilienhaus verkaufen',
                'description' => 'Professionelle Ersteinschätzung für Eigentümer von Mehrfamilienhäusern und Wohninvestments.',
                'eyebrow' => 'Mehrfamilienhaus & Investment',
                'hero_title' => 'Mehrfamilienhaus verkaufen – fundiert einwerten, professionell vermarkten',
                'hero_text' => 'Bei Mehrfamilienhäusern stehen Ertrag, Mietstruktur, Entwicklungspotenzial und Käuferzielgruppe im Mittelpunkt. Wir schaffen die Grundlage für eine professionelle Vermarktung.',
                'target_audience' => 'Private und gewerbliche Eigentümer von Mehrfamilienhäusern',
                'keywords' => ['Mehrfamilienhaus verkaufen', 'Mietshaus verkaufen', 'Zinshaus verkaufen'],
            ],
            [
                'slug' => 'grundstueck-verkaufen',
                'title' => 'Grundstück verkaufen',
                'description' => 'Ersteinschätzung und Verkaufsbegleitung für Eigentümer unbebauter oder entwicklungsfähiger Grundstücke.',
                'eyebrow' => 'Grundstücksverkauf',
                'hero_title' => 'Grundstück verkaufen – Potenzial erkennen, Wert realistisch einordnen',
                'hero_text' => 'Bei Grundstücken entscheiden Lage, Größe, Zuschnitt und mögliche Bebaubarkeit über den Marktwert. Wir verschaffen Ihnen eine erste Orientierung für den Verkauf.',
                'target_audience' => 'Eigentümer unbebauter oder entwicklungsfähiger Grundstücke',
                'keywords' => ['Grundstück verkaufen', 'Baugrundstück verkaufen', 'Grundstück bewerten'],
            ],

            // Eigene Zielgruppe mit besonderem Prozess: gesetzliche Betreuer.
            [
                'slug' => 'immobilienverkauf-gesetzliche-betreuer',
                'title' => 'Immobilienverkauf für gesetzliche Betreuer',
                'description' => 'Ein strukturierter Immobilienverkaufsprozess speziell für gesetzliche Betreuer und Betreuungsfälle.',
                'eyebrow' => 'Speziell für gesetzliche Betreuer',
                'hero_title' => 'Immobilienverkauf im Betreuungsfall – mit einem klaren Prozess für gesetzliche Betreuer',
                'hero_text' => 'Für Immobilienverkäufe im Betreuungsfall bieten wir einen eigenen, nachvollziehbaren Ablauf: von der marktgerechten Ersteinschätzung über die strukturierte Zusammenstellung der Objektunterlagen bis zur professionellen Vermarktung. So erhalten Sie eine belastbare Grundlage für die weiteren erforderlichen Schritte.',
                'target_audience' => 'Gesetzliche Betreuer, Berufsbetreuer und Betreuungsvereine mit Immobilienfällen',
                'keywords' => ['Immobilie gesetzlicher Betreuer verkaufen', 'Hausverkauf Betreuung', 'Immobilienverkauf Betreuungsgericht', 'Berufsbetreuer Immobilie verkaufen'],
            ],
        ];
    }
}
