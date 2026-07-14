import { useEffect, useRef } from 'react';
import { Button } from './ui/Button';

export function CalendlyModal({ url, isOpen, onClose }) {
    const containerRef = useRef(null);

    useEffect(() => {
        if (!isOpen || !url) return;

        const scriptId = 'calendly-widget-script';
        let script = document.getElementById(scriptId);

        if (!script) {
            script = document.createElement('script');
            script.id = scriptId;
            script.src = 'https://assets.calendly.com/assets/external/widget.js';
            script.async = true;
            document.body.appendChild(script);
        }

        const render = () => {
            if (window.Calendly && containerRef.current) {
                containerRef.current.innerHTML = '';
                window.Calendly.initInlineWidget({
                    url: url,
                    parentElement: containerRef.current,
                    prefill: {},
                    utm: {},
                });
            }
        };

        if (window.Calendly) {
            render();
        } else {
            script.addEventListener('load', render);
        }

        return () => {
            if (script) {
                script.removeEventListener('load', render);
            }
        };
    }, [isOpen, url]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div className="relative w-full max-w-3xl rounded-2xl bg-white p-4 shadow-xl">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-bold text-foreground">Termin vereinbaren</h3>
                    <Button variant="ghost" onClick={onClose} aria-label="Schließen">
                        ✕
                    </Button>
                </div>
                <div ref={containerRef} className="min-h-[600px] w-full" />
            </div>
        </div>
    );
}
