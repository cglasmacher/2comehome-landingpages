import Icon from '@/Components/ui/Icon';
import { formatCurrency } from '@/lib/utils';
import '../../../css/market-context-card.css';

const defaultExamples = [
    { type: 'Wohnung', price: 269000 },
    { type: 'Reihenhaus', price: 535000 },
    { type: 'Einfamilienhaus', price: 733000 },
    { type: 'Doppelhaushälfte', price: 489000 },
    { type: 'Eigentumswohnung', price: 318000 },
    { type: 'Mehrfamilienhaus', price: 1145000 },
    { type: 'Bungalow', price: 598000 },
    { type: 'Penthouse', price: 648000 },
    { type: 'Stadthaus', price: 679000 },
    { type: 'Dachgeschosswohnung', price: 349000 },
    { type: 'Erdgeschosswohnung', price: 295000 },
    { type: 'Zweifamilienhaus', price: 865000 },
];

const markerLayouts = [
    [
        { top: '17%', left: '9%' },
        { top: '42%', right: '8%' },
        { bottom: '16%', left: '28%' },
    ],
    [
        { top: '14%', right: '12%' },
        { top: '46%', left: '7%' },
        { bottom: '13%', right: '21%' },
    ],
    [
        { top: '22%', left: '18%' },
        { top: '31%', right: '6%' },
        { bottom: '12%', left: '43%' },
    ],
    [
        { top: '12%', left: '38%' },
        { top: '48%', right: '10%' },
        { bottom: '15%', left: '8%' },
    ],
];

function seedFromString(value) {
    return Array.from(String(value || '2comehome')).reduce(
        (seed, character) => ((seed * 31) + character.charCodeAt(0)) >>> 0,
        2166136261,
    );
}

function seededShuffle(items, seed) {
    const result = [...items];
    let state = seed || 1;

    for (let index = result.length - 1; index > 0; index -= 1) {
        state = (state * 1664525 + 1013904223) >>> 0;
        const swapIndex = state % (index + 1);
        [result[index], result[swapIndex]] = [result[swapIndex], result[index]];
    }

    return result;
}

export default function MarketContextCard({ content = {}, valuation, property = {}, ctaHref = '#bewertung', ctaLabel }) {
    const defaultLines = [
        'Wie ordnet sich Ihre Immobilie im direkten Marktumfeld ein?',
        'Welche vergleichbaren Immobilien sind für die Einordnung relevant?',
        'Wie entwickeln sich Angebot, Nachfrage und Preise in Ihrer Lage?',
        'Diese Faktoren betrachten wir gemeinsam, um aus der ersten Orientierung eine belastbare Marktanalyse zu machen.',
    ];

    const lines = Array.isArray(content?.body) && content.body.length > 0
        ? content.body
        : defaultLines;

    const configuredExamples = Array.isArray(content?.examples) && content.examples.length >= 3
        ? content.examples
            .filter((example) => example?.type && Number(example?.price) > 0)
            .map((example) => ({ type: String(example.type), price: Number(example.price) }))
        : defaultExamples;

    const regionLabel = property?.city ? `${property.city} & Umgebung` : 'Ihre Region';
    const seed = seedFromString(`${property?.city ?? ''}|${property?.property_type ?? ''}|${valuation?.estimated_value ?? ''}`);
    const visibleExamples = seededShuffle(configuredExamples, seed).slice(0, 3);
    const positions = markerLayouts[seed % markerLayouts.length];
    const isDemoData = content?.examples_are_real !== true;

    return (
        <section className="market-context-card" aria-labelledby="market-context-title">
            <div className="market-context-content">
                <p className="eyebrow"><span className="short-rule" />{content?.eyebrow ?? 'Markt & Vergleich'}</p>
                <h2 id="market-context-title">{content?.title ?? 'Was passiert am Immobilienmarkt rund um Ihre Immobilie?'}</h2>
                <div className="market-context-copy">
                    {lines.map((line) => <p key={line}>{line}</p>)}
                </div>
                <a className="btn-primary market-context-cta" href={ctaHref}>
                    {ctaLabel ?? content?.button_label ?? 'Jetzt Immobilienwert ermitteln'}
                    <Icon name="arrow" size={18} />
                </a>
            </div>

            <div className="sold-examples-panel">
                <div className="sold-examples-heading">
                    <div>
                        <p className="sold-examples-kicker">{regionLabel}</p>
                        <h3>Verkaufte Objekte in Ihrer Region</h3>
                    </div>
                    {isDemoData && <span className="sold-examples-demo">Beispieldaten</span>}
                </div>

                <div className="sold-examples-map" aria-label="Drei beispielhafte verkaufte Immobilien">
                    <svg className="sold-examples-map-art" viewBox="0 0 520 360" role="presentation" focusable="false" aria-hidden="true">
                        <rect x="0" y="0" width="520" height="360" rx="18" />
                        <g className="sold-map-blocks">
                            <path d="M24 34h120v68H24zM374 20h122v84H374zM190 118h112v70H190zM35 232h132v88H35zM343 228h140v95H343z" />
                            <path d="M318 54h38v126h-38zM177 205h118v39H177z" />
                        </g>
                        <g className="sold-map-roads">
                            <path d="M-10 181C78 143 141 159 211 130S366 82 548 119" />
                            <path d="M88 -20C110 71 93 132 131 203s39 105 25 184" />
                            <path d="M279 -10c-8 72 14 111 5 164s-40 100-20 217" />
                            <path d="M-20 296c102-39 173-22 248-11s166 17 312-31" />
                            <path d="M417 -20c-14 68-16 132 13 184s28 113 18 214" />
                        </g>
                        <g className="sold-map-minor-roads">
                            <path d="M7 116h152M335 165h183M203 30v72M319 254v91M12 214h114M376 122v85" />
                        </g>
                    </svg>

                    {visibleExamples.map((example, index) => (
                        <div
                            className="sold-object-flag"
                            key={`${example.type}-${example.price}`}
                            style={positions[index]}
                        >
                            <span className="sold-object-status"><i /> verkauft</span>
                            <strong>{example.type}</strong>
                            <b>{formatCurrency(example.price)}</b>
                        </div>
                    ))}
                </div>

                <div className="sold-examples-footer">
                    <span>3 von {configuredExamples.length} Vergleichsbeispielen</span>
                    {isDemoData
                        ? <small>Demodarstellung – echte Referenzobjekte können später hinterlegt werden.</small>
                        : <small>Auswahl aus hinterlegten Referenzobjekten.</small>}
                </div>
            </div>
        </section>
    );
}
