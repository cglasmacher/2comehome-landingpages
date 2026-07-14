export default function ValuationRange({ valuation, calendlyUrl, reportUrl }) {
    const euro = (value) => new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(value);

    return (
        <div className="rounded-2xl bg-white p-8 shadow-xl">
            <p className="text-sm font-semibold uppercase tracking-wide text-slate-500">Ihre erste Einwertung</p>
            <h2 className="mt-2 text-3xl font-bold">
                {valuation.range_low && valuation.range_high
                    ? `${euro(valuation.range_low)} bis ${euro(valuation.range_high)}`
                    : 'Bewertung wird geprüft'}
            </h2>
            <p className="mt-3 text-slate-600">
                Die Range basiert auf einer automatisierten PriceHubble-Ersteinschätzung und ersetzt keine persönliche Marktanalyse.
            </p>

            <div className="mt-6 flex flex-wrap gap-3">
                {calendlyUrl && (
                    <a href={calendlyUrl} target="_blank" className="rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white">
                        Kostenlose Erstberatung buchen
                    </a>
                )}
                {reportUrl && (
                    <a href={reportUrl} target="_blank" className="rounded-xl border px-5 py-3 font-semibold">
                        PDF-Bericht öffnen
                    </a>
                )}
            </div>
        </div>
    );
}
