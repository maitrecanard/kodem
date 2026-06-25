import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? 'border-cobalt-600 bg-cobalt-50 text-cobalt-700 focus:border-cobalt-700 focus:bg-cobalt-100 focus:text-cobalt-800'
                    : 'border-transparent text-acier hover:border-brume hover:bg-cobalt-50 hover:text-encre focus:border-brume focus:bg-cobalt-50 focus:text-encre'
            } text-base font-medium transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {children}
        </Link>
    );
}
