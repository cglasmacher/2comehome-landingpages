import { cn } from '@/lib/utils';

export function Label({ children, className, ...props }) {
    return (
        <label className={cn('label', className)} {...props}>
            {children}
        </label>
    );
}
