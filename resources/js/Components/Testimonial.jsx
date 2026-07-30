/**
 * Testimonial — charte KODEM §5.
 * Rules:
 * - Required fields: citation, nom, fonction, structure.
 * - Optional: photo (img with loading="lazy" + width/height), cas_lie.
 * - Never renders Review/AggregateRating schema.
 * - Never shows anonymous or placeholder testimonials.
 */

/**
 * Single testimonial card.
 *
 * @param {{ citation: string, nom: string, fonction: string, structure: string, photo?: string }} props
 */
export function Testimonial({ citation, nom, fonction, structure, photo, exemple = false }) {
    return (
        <figure className="kodem-card relative bg-white rounded-kodem border border-brume p-6 shadow-sm flex flex-col gap-4">
            {exemple && (
                <span className="absolute right-4 top-4 font-mono text-legende uppercase tracking-widest text-cobalt-600 border border-brume rounded px-2 py-0.5">
                    exemple
                </span>
            )}
            <blockquote className="text-encre leading-relaxed">
                <p>{citation}</p>
            </blockquote>
            <figcaption className="flex items-center gap-3 mt-auto pt-2 border-t border-brume">
                {photo && (
                    <img
                        src={photo}
                        alt={nom}
                        width={40}
                        height={40}
                        loading="lazy"
                        className="rounded-full object-cover w-10 h-10"
                    />
                )}
                <span className="text-sm text-acier">
                    <strong className="text-encre font-medium">{nom}</strong>
                    {' — '}
                    {fonction}, {structure}
                </span>
            </figcaption>
        </figure>
    );
}

/**
 * A testimonial is only complete (and displayable) when its four required
 * fields are present, non-empty, and not left as a "// TODO" placeholder.
 * Guarantees the charte §5 rule: never an anonymous or placeholder testimonial.
 */
function isComplete(t) {
    return ['citation', 'nom', 'fonction', 'structure'].every(
        (k) => typeof t?.[k] === 'string' && t[k].trim() !== '' && !t[k].startsWith('// TODO'),
    );
}

/**
 * Section rendering a list of testimonials.
 * Incomplete / placeholder entries are filtered out; returns null when nothing
 * real remains — never renders a placeholder.
 *
 * @param {{ items: Array }} props
 */
export function TestimonialSection({ items }) {
    const valid = (items || []).filter(isComplete);
    if (!valid.length) return null;

    return (
        <section className="mt-16">
            <div className="grid gap-6 md:grid-cols-2">
                {valid.map((t, i) => (
                    <Testimonial
                        key={i}
                        citation={t.citation}
                        nom={t.nom}
                        fonction={t.fonction}
                        structure={t.structure}
                        photo={t.photo}
                        exemple={t.exemple}
                    />
                ))}
            </div>
        </section>
    );
}
