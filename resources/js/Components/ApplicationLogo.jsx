/**
 * Symbole de marque KODEM — [k]
 * Charte p.05 : crochets cobalt, lettre en réserve.
 * Les crochets restent toujours cobalt ; la lettre « k » suit `currentColor`
 * (pilotée par les classes text-* du parent : encre sur fond clair, blanc sur fond foncé).
 */
export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            role="img"
            aria-label="kodem"
        >
            {/* Crochets — toujours cobalt */}
            <g fill="#2348E0">
                <path d="M21 12h-6a3 3 0 0 0-3 3v34a3 3 0 0 0 3 3h6v-6h-3V18h3v-6z" />
                <path d="M43 12h6a3 3 0 0 1 3 3v34a3 3 0 0 1-3 3h-6v-6h3V18h-3v-6z" />
            </g>
            {/* Lettre k — suit la couleur courante */}
            <text
                x="32"
                y="42"
                fill="currentColor"
                fontFamily="'Space Grotesk', sans-serif"
                fontSize="28"
                fontWeight="600"
                textAnchor="middle"
            >
                k
            </text>
        </svg>
    );
}
