import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function formatCurrency(value, options = {}) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return options.fallback ?? 'n/a';
    }

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
        ...options,
    }).format(Number(value));
}
