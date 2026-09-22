export default function ValuationProcessingState({ isProcessing }) {
    if (!isProcessing) {
        return null;
    }

    return (
        <div
            className="rounded-2xl border border-primary/15 bg-primary/5 p-4 sm:p-5"
            role="status"
            aria-live="polite"
            aria-atomic="true"
        >
            <div className="flex items-center gap-4">
                <span className="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-primary/25 bg-white text-primary">
                    <svg
                        viewBox="0 0 24 24"
                        className="h-7 w-7"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.7"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="8.5" />
                        <path d="M12 7.5v4.8l3.2 2" className="origin-center animate-spin" style={{ animationDuration: '2.2s' }} />
                    </svg>
                </span>

                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-(--color-secondary)">
                        Ihre Immobilienbewertung wird erstellt
                    </p>
                    <p className="mt-1 text-sm leading-5 text-(--color-muted)">
                        Bitte einen kurzen Moment warten. Wir prüfen Ihre Angaben und leiten Sie anschließend automatisch zu Ihrer Ersteinschätzung weiter.
                    </p>
                </div>
            </div>
        </div>
    );
}
