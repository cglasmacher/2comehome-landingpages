import Icon from '@/Components/ui/Icon';
import { formatCurrency } from '@/lib/utils';
import '../../../css/market-context-card.css';

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

    const hasRange = valuation?.status !== 'failed'
        && Number(valuation?.range_low) > 0
        && Number(valuation?.range_high) > 0;

    const regionLabel = property?.city ? `${property.city} & Umgebung` : 'Ihre Region';
    const rangeLabel = hasRange
        ? `${formatCurrency(valuation.range_low)} – ${formatCurrency(valuation.range_high)}`
        : 'Lokale Marktdaten';

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

            <div className="market-context-map" aria-hidden="true">
                <svg viewBox="0 0 300 360" role="presentation" focusable="false">
                    <path d="M149 16 174 28 184 53 208 64 216 89 239 111 226 135 237 159 220 179 231 204 214 225 222 250 205 270 210 299 187 319 171 344 147 330 125 340 105 318 81 310 75 286 55 270 62 245 46 225 60 203 50 180 67 159 56 136 78 120 83 96 105 83 109 58 130 48 136 27Z" />
                </svg>

                <div className="market-context-badge market-context-badge--primary">
                    <small>{regionLabel}</small>
                    <strong>{rangeLabel}</strong>
                </div>
                <div className="market-context-badge market-context-badge--micro"><span>Mikrolage</span></div>
                <div className="market-context-badge market-context-badge--comparables"><span>Vergleichsobjekte</span></div>
                <div className="market-context-badge market-context-badge--trend"><span>Preisentwicklung</span></div>
            </div>
        </section>
    );
}
