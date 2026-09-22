import { cn } from '@/lib/utils';

const steps = [
    {
        number: '1',
        title: 'Eingabe Ihrer Daten',
        description: 'Wir verarbeiten Ihre Daten sicher und vertraulich.',
    },
    {
        number: '2',
        title: 'unverbindliche Erstbewertung',
        description: 'Wir führen eine automatisierte Ersteinschätzung Ihrer Immobilie durch.',
    },
    {
        number: '3',
        title: 'Professionelle und diskrete Vermarktung',
        description: 'Wir vermarkten Ihre Immobilie professionell und diskret, sodass alle Beteiligten zufrieden sind.',
    },
];

export default function JourneySteps({ activeStep = 1, className }) {
    return (
        <div
            className={cn(
                'mt-10 rounded-[1.75rem] border border-(--color-border) bg-white/80 p-5 shadow-sm sm:p-6',
                className,
            )}
        >
            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                Ihr Weg von der Einwertung bis zum Notartermin
            </p>
            <ol aria-label="Ablauf der Immobilienvermarktung" className="mt-6 grid gap-6 md:grid-cols-3 md:gap-0">
                {steps.map((step, index) => {
                    const isActive = index + 1 === activeStep;

                    return (
                        <li
                            key={step.number}
                            aria-current={isActive ? 'step' : undefined}
                            className={`relative flex gap-4 md:block md:px-5 ${index === 0 ? 'md:pl-0' : ''} ${index === steps.length - 1 ? 'md:pr-0' : ''}`}
                        >
                            {index < steps.length - 1 && (
                                <span
                                    aria-hidden="true"
                                    className="absolute left-5 top-11 z-0 h-[calc(100%+1.5rem)] w-px bg-primary/20 md:left-1/2 md:top-5 md:h-px md:w-[calc(100%+2.5rem)]"
                                />
                            )}
                            <span
                                className={cn(
                                    'relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-xs font-bold tracking-[0.08em] md:mx-auto',
                                    isActive
                                        ? 'border-primary bg-primary text-white shadow-sm'
                                        : 'border-primary bg-white text-primary',
                                )}
                            >
                                {step.number}
                            </span>
                            <div className="relative z-10 pt-0.5 md:mt-4 md:text-center">
                                <h2 className={cn('text-sm font-bold text-(--color-secondary)', isActive && 'text-primary')}>
                                    {step.title}
                                </h2>
                                <p className="mt-1 text-sm text-(--color-muted)">{step.description}</p>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
