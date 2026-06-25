export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center rounded-md border border-cobalt-600 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-cobalt-700 shadow-sm transition duration-150 ease-in-out hover:bg-cobalt-50 focus:outline-none focus:ring-2 focus:ring-cobalt-500 focus:ring-offset-2 disabled:opacity-25 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
