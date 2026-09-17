import { useEffect, useRef, useState } from 'react';
import Icon from './ui/Icon';

export function safeCalendlyUrl(value) {
    try {
        const url = new URL(value);
        return url.protocol === 'https:' && (url.hostname === 'calendly.com' || url.hostname.endsWith('.calendly.com')) ? url : null;
    } catch { return null; }
}

export function CalendlyModal({ url, isOpen, onClose }) {
    const dialog = useRef(null);
    const [consented, setConsented] = useState(false);
    const [loading, setLoading] = useState(true);
    const safeUrl = safeCalendlyUrl(url);
    const embed = safeUrl ? new URL(safeUrl) : null;
    if (embed) {
        embed.searchParams.set('embed_type', 'Inline');
        embed.searchParams.set('hide_gdpr_banner', '0');
        embed.searchParams.set('primary_color', 'b44637');
    }

    useEffect(() => {
        const element = dialog.current;
        if (!isOpen || !element) return;
        element.showModal();
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        setLoading(true);
        return () => { element.close(); document.body.style.overflow = previous; };
    }, [isOpen]);

    if (!isOpen) return null;
    return (
        <dialog ref={dialog} className="booking-dialog" aria-labelledby="booking-title" onCancel={onClose} onClick={(event) => { if (event.target === dialog.current) onClose(); }}>
            <div className="booking-dialog-inner">
                <header className="booking-dialog-header">
                    <div><p className="eyebrow">Persönlich für Sie da</p><h2 id="booking-title">Ihr Termin für einen Rückruf</h2></div>
                    <button autoFocus type="button" className="icon-button" onClick={onClose} aria-label="Terminfenster schließen"><Icon name="close" /></button>
                </header>
                {!safeUrl ? <p className="booking-consent">Der Kalender ist derzeit nicht verfügbar. Bitte kontaktieren Sie uns per E-Mail.</p> : !consented ? (
                    <div className="booking-consent">
                        <span className="icon-tile"><Icon name="calendar" size={32} /></span>
                        <h3>Wann passt es Ihnen?</h3>
                        <p>Wählen Sie im Kalender einen freien Termin für unser Gespräch. Beim Laden wird eine Verbindung zum externen Anbieter Calendly hergestellt. Ihre Formulardaten werden nicht automatisch übertragen.</p>
                        <button type="button" className="btn-primary" onClick={() => setConsented(true)}>Kalender laden <Icon name="arrow" size={18} /></button>
                    </div>
                ) : (
                    <div className="calendar-frame">
                        {loading && <p className="calendar-loading" role="status">Kalender wird geladen …</p>}
                        <iframe src={embed.toString()} title="Freien Termin für einen Rückruf auswählen" onLoad={() => setLoading(false)} referrerPolicy="strict-origin-when-cross-origin" />
                    </div>
                )}
                {safeUrl && <footer className="booking-dialog-footer">Kalender nicht sichtbar? <a href={safeUrl.toString()} target="_blank" rel="noopener noreferrer">In neuem Tab öffnen ↗</a></footer>}
            </div>
        </dialog>
    );
}
