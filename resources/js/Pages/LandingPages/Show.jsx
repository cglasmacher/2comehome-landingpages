import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { route } from 'ziggy-js';
import { ValuationForm } from '@/Components/ValuationForm';
import ValuationRange from '@/Components/LandingPages/ValuationRange';
import { CalendlyModal } from '@/Components/CalendlyModal';

export default function Show() {
    const { page, valuation, report_url, flash } = usePage().props;
    const [isCalendlyOpen, setIsCalendlyOpen] = useState(false);

    return (
        <>
            <Head title={page.seo?.title ?? page.title}>
                {page.seo?.description && <meta name="description" content={page.seo.description} />}
            </Head>

            <div className="min-h-screen bg-(--color-background)">
                <header className="bg-white shadow-xs">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                        <a href="/" className="flex items-center gap-3">
                            <img
                                src="/images/logo-2comehome.png"
                                alt="2COME HOME Immobilien"
                                className="h-14 w-auto"
                            />
                        </a>
                        {page.description && (
                            <p className="hidden text-sm text-(--color-muted) sm:block">
                                {page.description}
                            </p>
                        )}
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-4 py-12">
                    <div className="grid gap-12 lg:grid-cols-2 lg:items-start">
                        <section>
                            <p className="mb-3 text-sm font-semibold uppercase tracking-wide text-primary">
                                {page.content?.eyebrow ?? 'Immobilienbewertung'}
                            </p>
                            <h1 className="text-4xl font-bold leading-tight text-(--color-secondary) md:text-5xl">
                                {page.content?.hero_title ?? 'Erfahren Sie den Wert Ihrer Immobilie'}
                            </h1>
                            <p className="mt-4 text-lg text-(--color-muted)">
                                {page.content?.hero_text ??
                                    'In nur wenigen Schritten erhalten Sie eine fundierte, unverbindliche Markteinschätzung.'}
                            </p>

                            <ul className="mt-8 space-y-3 text-(--color-foreground)">
                                <li className="flex items-center gap-3">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-(--color-primary) text-xs font-bold text-white">
                                        1
                                    </span>
                                    Objektdaten eingeben
                                </li>
                                <li className="flex items-center gap-3">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-(--color-primary) text-xs font-bold text-white">
                                        2
                                    </span>
                                    Sofortigen Wertbereich erhalten
                                </li>
                                <li className="flex items-center gap-3">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-(--color-primary) text-xs font-bold text-white">
                                        3
                                    </span>
                                    Optional Erstberatung buchen
                                </li>
                            </ul>
                        </section>

                        <section className="space-y-8">
                            {flash?.success && (
                                <div className="rounded-xl bg-green-50 p-4 text-sm text-green-800">
                                    {flash.success}
                                </div>
                            )}

                            {valuation ? (
                                <>
                                    <ValuationRange
                                        valuation={valuation}
                                        reportUrl={report_url}
                                    />
                                    <div className="mt-4">
                                        {page.calendly_url ? (
                                            <button
                                                onClick={() => setIsCalendlyOpen(true)}
                                                className="btn-primary w-full"
                                            >
                                                Kostenlose Erstberatung buchen
                                            </button>
                                        ) : null}
                                    </div>
                                    <CalendlyModal
                                        url={page.calendly_url}
                                        isOpen={isCalendlyOpen}
                                        onClose={() => setIsCalendlyOpen(false)}
                                    />
                                </>
                            ) : (
                                <ValuationForm action={route('landing-pages.leads.store', page.slug)} />
                            )}
                        </section>
                    </div>
                </main>

                <footer className="border-t border-(--color-border) bg-white py-8">
                    <div className="mx-auto max-w-6xl px-4 text-center text-sm text-(--color-muted)">
                        &copy; {new Date().getFullYear()} {page.title}. Alle Rechte vorbehalten.
                    </div>
                </footer>
            </div>
        </>
    );
}
