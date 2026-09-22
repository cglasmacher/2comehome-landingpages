import { Head, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import ValuationResult from '@/Components/LandingPages/ValuationResult';
import { ValuationForm } from '@/Components/ValuationForm';
import Icon from '@/Components/ui/Icon';

export default function Show() {
    const { page, valuation, report_url, lead_summary, email_status, property_types = [] } = usePage().props;
    return (
        <>
            <Head title={page.seo?.title ?? page.title}>{page.seo?.description && <meta name="description" content={page.seo.description} />}</Head>
            <a className="skip-link" href="#main">Zum Inhalt</a>
            <div className="site-shell">
                <header className="site-header">
                    <div className="site-container header-inner">
                        <a href={route('landing-pages.show', page.slug)} aria-label="2 COME HOME – zur Landingpage"><img src="/images/logo-2comehome.png" alt="2 COME HOME Immobilien" className="brand-logo" /></a>
                        <h1 className="header-headline">Kostenlose Ersteinschätzung und Beratung</h1>
                        <a className="header-link" href={valuation ? '#rueckruf' : '#bewertung'}>{valuation ? 'Gespräch vereinbaren' : 'Zur Bewertung'} <Icon name="arrow" size={17} /></a>
                    </div>
                </header>
                <main id="main" className="site-container page-main">
                    {valuation ? <ValuationResult valuation={valuation} summary={lead_summary} reportUrl={report_url} calendlyUrl={page.calendly_url} emailStatus={email_status} /> : (
                        <div className="valuation-layout">
                            <section className="intro-panel">
                                <p className="eyebrow"><span className="short-rule" />{page.content?.eyebrow ?? 'Ihre Immobilienbewertung'}</p>
                                <h1>{page.content?.hero_title ?? 'Was steckt in Ihrer Immobilie?'}</h1>
                                <p className="intro-copy">{page.content?.hero_text ?? 'Erhalten Sie eine erste Orientierung zum Marktwert Ihrer Immobilie. Wir begleiten Sie bei den nächsten Schritten.'}</p>
                                <div className="intro-benefits">
                                    <div><Icon name="file" /><span>Ihre Ersteinschätzung als PDF</span></div>
                                    <div><Icon name="phone" /><span>Ein persönlicher Ansprechpartner</span></div>
                                </div>
                                <div className="process-note">
                                    <span className="process-number">01—03</span>
                                    <h2>Ein guter Anfang für Ihre Entscheidung.</h2>
                                    <ol><li><span>01</span> Immobilie beschreiben</li><li><span>02</span> Ersteinschätzung erhalten</li><li><span>03</span> Gemeinsam die nächsten Schritte besprechen</li></ol>
                                </div>
                                <p className="intro-footnote">Unverbindliche Orientierung – keine verbindliche Verkehrswertermittlung.</p>
                            </section>
                            <section id="bewertung" className="form-panel"><ValuationForm action={route('landing-pages.leads.store', page.slug)} propertyTypes={property_types} /></section>
                        </div>
                    )}
                </main>
                <footer className="site-footer"><div className="site-container footer-inner"><span>© {new Date().getFullYear()} 2 COME HOME Immobilien</span><span>Ihre Immobilie verdient persönliche Aufmerksamkeit.</span></div></footer>
            </div>
        </>
    );
}
