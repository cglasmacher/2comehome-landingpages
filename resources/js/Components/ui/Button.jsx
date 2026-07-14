import { cn } from '@/lib/utils';

export function Button({
    children,
    variant = 'primary',
    className,
    isLoading = false,
    disabled,
    type = 'button',
    ...props
}) {
    const variants = {
        primary: 'btn-primary',
        secondary: 'btn-secondary',
        outline: 'btn border-2 border-primary text-primary hover:bg-primary hover:text-white',
        ghost: 'btn text-foreground hover:bg-gray-100',
    };

    return (
        <button
            type={type}
            disabled={disabled || isLoading}
            className={cn(variants[variant], className)}
            {...props}
        >
            {isLoading && (
                <svg
                    className="mr-2 h-4 w-4 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        className="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        strokeWidth="4"
                    />
                    <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
            )}
            {children}
        </button>
    );
}
