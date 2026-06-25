/**
 * Logotype KODEM — [kodem]
 * Charte p.04 : bas-de-casse, Space Grotesk Medium, interlettrage -3 %.
 * Les crochets sont toujours cobalt et solidaires du mot (jamais isolés).
 * Le mot suit `currentColor` : encre sur fond clair, blanc sur fond foncé.
 */
export default function BrandWordmark({ className = '', bracketClassName = 'text-cobalt-600' }) {
    return (
        <span
            className={`inline-flex items-baseline font-sans font-medium leading-none ${className}`}
            style={{ letterSpacing: '-0.03em' }}
            aria-label="kodem"
        >
            <span className={bracketClassName} aria-hidden="true">[</span>
            <span>kodem</span>
            <span className={bracketClassName} aria-hidden="true">]</span>
        </span>
    );
}
