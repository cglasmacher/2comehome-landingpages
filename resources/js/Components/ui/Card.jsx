import { cn } from '@/lib/utils';

export function Card({ children, className }) {
    return <div className={cn('card', className)}>{children}</div>;
}

export function CardTitle({ children, className }) {
    return <h3 className={cn('mb-2 text-xl font-bold text-foreground', className)}>{children}</h3>;
}
