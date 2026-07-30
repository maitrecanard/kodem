import DiagonalPattern from '@/Components/DiagonalPattern';

/**
 * CaseImage — encart visuel d'une réalisation.
 *
 * Si `cas.image` est un chemin réel → <img> WebP en lazy-load.
 * Sinon (image absente ou encore "// TODO") → réserve d'image charte KODEM
 * (motif diagonal brume + étiquette mono), jamais d'image cassée affichée.
 * Convention de fichier : /images/realisations/<slug>.webp (WebP < 300 ko).
 *
 * Le même fichier sert le format carte (3/2) et le format hero (16/9) : le
 * recadrage `object-cover` rogne donc différemment selon le contexte. Quand le
 * sujet du cas n'est pas au centre du cadre (ex. la carte Raspberry Pi posée en
 * bas de l'image), `cas.image_focus` déplace le point d'ancrage pour qu'il reste
 * visible. Valeurs admises ci-dessous ; toute autre valeur retombe sur 'center'.
 *
 * @param {{ cas: object, variant?: 'card'|'hero', className?: string }} props
 */
const FOCUS_CLASSES = {
    top: 'object-top',
    center: 'object-center',
    bottom: 'object-bottom',
    left: 'object-left',
    right: 'object-right',
};

export default function CaseImage({ cas, variant = 'card', className = '' }) {
    if (!cas) return null;

    const ratio = variant === 'hero' ? 'aspect-[16/9]' : 'aspect-[3/2]';
    // Contrat de donnée strict : seul un chemin WebP servi depuis public/images/realisations
    // est accepté. Toute autre valeur (sentinelle "// TODO", chemin externe, extension
    // inattendue) retombe sur la réserve d'image charte.
    const hasImage =
        typeof cas.image === 'string' && /^\/images\/realisations\/[\w-]+\.webp$/.test(cas.image);

    if (hasImage) {
        return (
            <img
                src={cas.image}
                alt={cas.titre}
                loading={variant === 'hero' ? 'eager' : 'lazy'}
                fetchpriority={variant === 'hero' ? 'high' : undefined}
                decoding="async"
                className={`${ratio} w-full rounded-kodem border border-brume object-cover ${
                    FOCUS_CLASSES[cas.image_focus] ?? FOCUS_CLASSES.center
                } ${className}`}
            />
        );
    }

    return (
        <DiagonalPattern
            className={`${ratio} w-full rounded-kodem border border-brume flex flex-col items-center justify-center gap-2 text-center ${className}`}
            role="img"
            aria-label={`Visuel à venir — ${cas.titre}`}
        >
            <span className="kodem-eyebrow">// visuel à venir</span>
            {cas.secteur && (
                <span className="font-mono text-legende text-acier">{cas.secteur}</span>
            )}
        </DiagonalPattern>
    );
}
