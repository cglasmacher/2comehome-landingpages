import { useState } from 'react';
import { CalendlyModal, safeCalendlyUrl } from '@/Components/CalendlyModal';
import Icon from '@/Components/ui/Icon';
import { formatCurrency } from '@/lib/utils';


export default function ValuationResult({ valuation, summary, reportUrl, calendlyUrl, emailStatus }) {
    const [bookingOpen, setBookingOpen] = useState(false);
    const contact = summary?.contact ?? {};
    const property = summary?.property ?? {};
    const hasRange = valuation?.status !== 'failed' && Number(valuation?.range_low) > 0 && Number(valuation?.range_high) > 0;
    const address = [[property.street, property.house_number].filter(Boolean).join(' '), [property.zip, property.city].filter(Boolean).join(' ')].filter(Boolean).join(', ');
    const validCalendar = safeCalendlyUrl(calendlyUrl);
    const details = [
        ['Immobilienart', property.property_type_label ?? property.property_type],
        ['Wohnfläche', property.living_area ? property.living_area + ' m²' : null],
        ['Grundstück', property.plot_area ? property.plot_area + ' m²' : null],
        ['Baujahr', property.construction_year],
        ['Zimmer', property.rooms],
    ];
    return (
        <div className="result-page">
            <div className="result-intro"><span className="success-label"><Icon name="check" size={17} /> Ihre Anfrage ist eingegangen</span><h1>Vielen Dank{contact.first_name ? ', ' + contact.first_name : ''}.</h1><p>Der erste Schritt ist gemacht. Hier finden Sie Ihre Angaben und die nächsten Schritte.</p></div>
            <div className="result-grid">
                <div className="result-main">
                    <section className="estimate-card">
                        <div className="estimate-heading"><p className="eyebrow">Ihre Immobilien-Ersteinschätzung</p><Icon name="home" size={28} /></div>
                        <h2>{hasRange ? 'Eine erste Orientierung für Sie.' : 'Wir schauen persönlich genauer hin.'}</h2>
                        {hasRange ? <div className="estimate-numbers"><strong>{formatCurrency(valuation.range_low)}</strong><span>bis</span><strong>{formatCurrency(valuation.range_high)}</strong></div> : <p className="estimate-pending">Eine automatische Einschätzung ist derzeit nicht verfügbar. Ihre Anfrage liegt uns vor – wir prüfen die Angaben persönlich.</p>}
                        {hasRange && <div className="estimate-bar" aria-hidden="true"><i /><b /><i /></div>}
                        {hasRange && valuation.source_label && <p className="estimate-disclaimer">Bewertungsquelle: {valuation.source_label}</p>}
                        <p className="estimate-disclaimer">Unverbindliche, automatisierte Ersteinschätzung. Sie ersetzt keine persönliche Marktanalyse und ist keine verbindliche Verkehrswertermittlung.</p>
                        {reportUrl && <a href={reportUrl} target="_blank" rel="noopener noreferrer" className="report-link"><span className="icon-tile"><Icon name="file" /></span><span><strong>Ihr Bericht als PDF</strong><small>Alle Angaben und die Ersteinschätzung</small></span><Icon name="arrow" size={20} /></a>}
                    </section>
                    <section className="summary-card"><div className="section-heading"><h2>Ihre Immobilie im Überblick</h2><Icon name="pin" /></div>{address && <p className="property-address">{address}</p>}<dl className="summary-grid">{details.filter(([, value]) => value).map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl>{summary?.notes && <div className="summary-note"><h3>Ihre Nachricht</h3><p>{summary.notes}</p></div>}</section>
                </div>
                <aside className="result-sidebar">
                    <section id="rueckruf" className="callback-card">
                        <span className="callback-icon"><Icon name="phone" size={28} /></span><p className="eyebrow">Der nächste Schritt</p>
                        <h2>Lassen Sie uns über Ihre Immobilie sprechen.</h2><p>Was bedeutet die Einschätzung für Sie? Welche Möglichkeiten haben Sie? Das besprechen wir am besten persönlich.</p>
                        {validCalendar ? <><button type="button" className="btn-primary booking-cta" onClick={() => setBookingOpen(true)}><Icon name="calendar" size={20} /> Rückruftermin wählen <Icon name="arrow" size={18} /></button><small>Sie wählen den Termin. Wir rufen Sie an.</small></> : <><a href="mailto:c.glasmacher@2comehome.de?subject=R%C3%BCckruf%20zur%20Immobilienbewertung" className="btn-primary booking-cta">Rückruf per E-Mail anfragen <Icon name="arrow" size={18} /></a><small>Die Online-Terminwahl ist momentan nicht verfügbar.</small></>}
                    </section>
                    <section className="email-notice"><Icon name="file" size={24} /><div><h2>{emailStatus === 'sent' ? 'Ihr Bericht ist unterwegs.' : 'Ihr Bericht zum Mitnehmen.'}</h2><p>{emailStatus === 'sent' ? 'Die E-Mail mit Ihrem PDF-Bericht wurde an den Mailserver übergeben:' : 'Der PDF-Bericht steht hier zum Download bereit. Der Versand per E-Mail ist noch nicht bestätigt.'}</p>{contact.email && <strong>{contact.email}</strong>}{emailStatus === 'sent' && <p className="email-hint">Bitte prüfen Sie gegebenenfalls auch Ihren Spamordner.</p>}</div></section>
                </aside>
            </div>
            <CalendlyModal url={calendlyUrl} isOpen={bookingOpen} onClose={() => setBookingOpen(false)} />
        </div>
    );
}
