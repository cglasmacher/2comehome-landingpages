import { formatCurrency } from '@/lib/utils';

export default function ValuationRange({ valuation, reportUrl }) {
    return (
        <div className="rounded-2xl bg-white p-8 shadow-xl border-t-4 border-t-primary">
            <p className="text-sm font-semibold uppercase tracking-wide text-primary">Ihre erste Einwertung</p>
            <h2 className="mt-2 text-3xl font-bold text-(--color-foreground)">
                {valuation.range_low && valuation.range_high
                    ? `${formatCurrency(valuation.range_low)} bis ${formatCurrency(valuation.range_high)}`
                    : 'Bewertung wird geprüft'}
            </h2>
            <p className="mt-3 text-(--color-muted)">
                Die Range basiert auf einer automatisierten PriceHubble-Ersteinschätzung und ersetzt keine persönliche Marktanalyse.
            </p>

            {reportUrl && (
                <div className="mt-6 flex flex-wrap gap-3">
                    <a href={reportUrl} target="_blank" rel="noreferrer" className="btn-outline">
                        PDF-Bericht öffnen
                    </a>
                </div>
            )}
        </div>
    );
}
