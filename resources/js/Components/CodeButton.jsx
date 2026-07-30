import { Link } from '@inertiajs/react';
const BASE = 'inline-flex items-center rounded-kodem bg-encre px-5 py-3 font-mono text-sm text-white transition-colors duration-[var(--kodem-dur-2)] active:translate-y-px hover:bg-cobalt-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-cobalt-500 focus-visible:ring-offset-2';
export default function CodeButton({ href, children, className = '', ...props }) {
    const cls = `${BASE} ${className}`;
    if (href) return <Link href={href} className={cls} {...props}>{children}</Link>;
    return <button className={cls} {...props}>{children}</button>;
}
