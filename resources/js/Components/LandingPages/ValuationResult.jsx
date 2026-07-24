import { useState } from 'react';
import { CalendlyModal } from '@/Components/CalendlyModal';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import ValuationRange from '@/Components/LandingPages/ValuationRange';
import { formatCurrency } from '@/lib/utils';

const resultImageUrl =
    'https://images.unsplash.com/photo-1632120377007-c2adc3017b1e?crop=entropy&cs=srgb&fm=jpg&ixid=M3w3NTAwNDR8MHwxfHNlYXJjaHwxfHxMdXh1cmlvdXMlMjBjb3p5JTIwbW9kZXJuJTIwbGl2aW5nJTIwcm9vbSUyMHdpdGglMjB3YXJtJTIwYW1iaWVudCUyMGxpZ2h0aW5nJTIwYW5kJTIwcmljaCUyMHRleHR1cmVzfGVufDB8fHx8MTc4NDg4NTMzM3ww&ixlib=rb-4.1.0&q=85';

function SummaryItem({ label, value }) {
    if (!value) {
        return null;
    }

    return (
        <div>
            <dt className="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-(--color-muted)">{label}</dt>
            <dd className="mt-1 text-sm leading-6 text-(--color-foreground)">{value}</dd>
        </div>
    );
}

function propertyAddress(property) {
    const street = [property?.street, property?.house_number].filter(Boolean).join(' ');
    const city = [property?.zip, property?.city].filter(Boolean).join(' ');

    return [street, city].filter(Boolean).join(', ');
}

export default function ValuationResult({ valuation, summary, reportUrl, calendlyUrl, successMessage }) {
    const [isCalendlyOpen, setIsCalendlyOpen] = useState(false);
    const contact = summary?.contact ?? {};
    const property = summary?.property ?? {};
    const firstName = contact.first_name ? `, ${contact.first_name}` : '';
    const estimatedValue = valuation?.estimated_value ? formatCurrency(valuation.estimated_value) : null;

    return (
        <div className="overflow-hidden rounded-[2rem] border border-(--color-border) bg-white shadow-xl shadow-black/5">
            <div className="grid lg:grid-cols-[1.08fr_0.92fr]">
                <div className="relative order-2 min-h-[24rem] overflow-hidden bg-(--color-secondary) text-white sm:min-h-[30rem] lg:order-1 lg:min-h-[44rem]">
                    <img
                        src={resultImageUrl}
                        alt="Modernes Wohnzimmer mit warmem Licht – Foto: Spacejoy auf Unsplash"
                        className="absolute inset-0 h-full w-full object-cover opacity-75"
                    />
                    <div className="absolute inset-0 bg-(--color-secondary)/75" />
                    <div className="relative z-10 flex h-full min-h-[24rem] flex-col justify-between p-8 sm:min-h-[30rem] sm:p-10 lg:min-h-[44rem] lg:p-12">
                        <div>
                            <div className="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.18em] text-white/80">
                                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-base text-white">✓</span>
                                Bewertung erhalten
                            </div>
                            <p className="mt-6 max-w-sm text-3xl font-semibold leading-tight sm:text-4xl">
                                Wir kümmern uns persönlich um Ihre Immobilie.
                            </p>
                        </div>
                        <p className="text-sm leading-6 text-white/75">Foto: Spacejoy auf Unsplash</p>
                    </div>
                </div>

                <div className="order-1 p-6 sm:p-10 lg:order-2 lg:p-12">
                    <div className="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.18em] text-primary">
                        <span className="h-2 w-2 rounded-full bg-primary" />
                        Ersteinschätzung abgeschlossen
                    </div>
                    <h1 className="mt-5 max-w-xl text-3xl font-bold leading-tight tracking-tight text-(--color-secondary) sm:text-4xl">
                        Vielen Dank für Ihre Anfrage{firstName}.
                    </h1>
                    <p className="mt-4 max-w-xl text-base leading-7 text-(--color-muted)">
                        {successMessage ?? 'Ihre Angaben sind bei uns eingegangen.'} Wir prüfen die Details persönlich und melden uns schnellstmöglich bei Ihnen.
                    </p>

                    <div className="mt-8">
                        <ValuationRange valuation={valuation} reportUrl={reportUrl} />
                    </div>

                    {estimatedValue && (
                        <div className="mt-4 flex items-baseline justify-between gap-4 border-b border-(--color-border) pb-5">
                            <span className="text-sm text-(--color-muted)">Automatischer Schätzwert</span>
                            <strong className="text-lg text-(--color-secondary)">{estimatedValue}</strong>
                        </div>
                    )}

                    <Card className="mt-6 bg-(--color-background) p-5 shadow-none sm:p-6">
                        <div className="flex items-center justify-between gap-4">
                            <h2 className="text-lg font-bold text-(--color-secondary)">Ihre Angaben im Überblick</h2>
                            <span className="text-xs font-semibold uppercase tracking-[0.14em] text-(--color-muted)">Resümee</span>
                        </div>
                        <dl className="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                            <SummaryItem label="Kontakt" value={[contact.first_name, contact.last_name].filter(Boolean).join(' ')} />
                            <SummaryItem label="E-Mail" value={contact.email} />
                            <SummaryItem label="Telefon" value={contact.phone} />
                            <SummaryItem label="Immobilie" value={property.property_type} />
                            <SummaryItem label="Adresse" value={propertyAddress(property)} />
                            <SummaryItem
                                label="Flächen"
                                value={[
                                    property.living_area && `${property.living_area} m² Wohnfläche`,
                                    property.plot_area && `${property.plot_area} m² Grundstück`,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            />
                            <SummaryItem label="Baujahr" value={property.construction_year} />
                            <SummaryItem label="Zimmer" value={property.rooms} />
                            <SummaryItem label="Notiz" value={summary?.notes} />
                        </dl>
                    </Card>

                    <div className="mt-6 rounded-2xl border border-primary/15 bg-primary/5 p-5 sm:p-6">
                        <p className="text-sm font-semibold text-(--color-secondary)">Wie geht es weiter?</p>
                        <p className="mt-2 text-sm leading-6 text-(--color-muted)">
                            Eine Zusammenfassung wurde an <strong className="font-semibold text-(--color-foreground)">{contact.email}</strong> gesendet. Wir melden uns schnellstmöglich persönlich bei Ihnen.
                        </p>
                        {calendlyUrl && (
                            <Button type="button" onClick={() => setIsCalendlyOpen(true)} className="mt-5 w-full sm:w-auto">
                                Kostenlose Erstberatung buchen
                            </Button>
                        )}
                    </div>

                    <CalendlyModal
                        url={calendlyUrl}
                        isOpen={isCalendlyOpen}
                        onClose={() => setIsCalendlyOpen(false)}
                    />
                </div>
            </div>
        </div>
    );
}
