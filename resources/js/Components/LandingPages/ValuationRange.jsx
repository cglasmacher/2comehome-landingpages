import { formatCurrency } from '@/lib/utils';

export default function ValuationRange({ valuation, reportUrl }) {
    const hasRange = Boolean(valuation?.range_low && valuation?.range_high);

    return (
        <div className="rounded-2xl border border-(--color-border) border-t-4 border-t-primary bg-(--color-background) p-7 sm:p-8">
            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Ihre erste Einwertung</p>
            <h2 className="mt-3 text-3xl font-bold leading-tight tracking-tight text-(--color-foreground) sm:text-4xl">
                {hasRange
                    ? `${formatCurrency(valuation.range_low)} bis ${formatCurrency(valuation.range_high)}`
                    : 'Persönliche Prüfung läuft'}
            </h2>
            <div className="mt-5 h-px w-20 bg-(--color-accent)" />
            <p className="mt-4 text-sm leading-6 text-(--color-muted)">
                {hasRange
                    ? 'Die Range basiert auf einer automatisierten PriceHubble-Ersteinschätzung und ersetzt keine persönliche Marktanalyse.'
                    : 'Die automatische Einwertung konnte nicht abschließend geladen werden. Unser Team übernimmt die Prüfung persönlich.'}
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
