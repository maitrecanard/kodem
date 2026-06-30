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
export function Testimonial({ citation, nom, fonction, structure, photo }) {
    return (
        <figure className="bg-white rounded-kodem border border-brume p-6 shadow-sm flex flex-col gap-4">
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
 * Section rendering a list of testimonials.
 * Returns null when items is empty or undefined — never renders a placeholder.
 *
 * @param {{ items: Array }} props
 */
export function TestimonialSection({ items }) {
    if (!items?.length) return null;

    return (
        <section className="mt-16">
            <div className="grid gap-6 md:grid-cols-2">
                {items.map((t, i) => (
                    <Testimonial
                        key={i}
                        citation={t.citation}
                        nom={t.nom}
                        fonction={t.fonction}
                        structure={t.structure}
                        photo={t.photo}
                    />
                ))}
            </div>
        </section>
    );
}
