import { useEffect, useState } from 'react';

function formatElapsedTime(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60)
        .toString()
        .padStart(2, '0');
    const seconds = (totalSeconds % 60).toString().padStart(2, '0');

    return `${minutes}:${seconds}`;
}

export default function ValuationProcessingState({ isProcessing }) {
    const [elapsedSeconds, setElapsedSeconds] = useState(0);

    useEffect(() => {
        if (!isProcessing) {
            setElapsedSeconds(0);
            return undefined;
        }

        const startedAt = Date.now();
        const timerId = window.setInterval(() => {
            setElapsedSeconds(Math.floor((Date.now() - startedAt) / 1000));
        }, 1000);

        return () => window.clearInterval(timerId);
    }, [isProcessing]);

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
                <span className="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-primary/30 bg-white text-primary">
                    <span className="absolute inset-1 rounded-full border border-primary/20" />
                    <span className="relative h-2.5 w-2.5 rounded-full bg-primary animate-pulse" />
                </span>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-(--color-secondary)">Ihre Immobilie wird gerade eingeordnet</p>
                    <p className="mt-1 text-sm leading-5 text-(--color-muted)">
                        PriceHubble prüft die Angaben. Einen Moment bitte – anschließend werden Sie direkt zur Ergebnisseite weitergeleitet.
                    </p>
                </div>
                <time className="shrink-0 font-mono text-lg font-semibold tabular-nums text-(--color-secondary)" dateTime={`PT${elapsedSeconds}S`}>
                    {formatElapsedTime(elapsedSeconds)}
                </time>
            </div>
        </div>
    );
}
