import SectionLabel from '@/Components/SectionLabel';

/**
 * CaseStudy — template §4 charte KODEM.
 * Renders a case study in order: problème → contrainte → choix → résultats.
 * Figures still marked "// TODO" (real §6 data not provided) are NEVER shown to
 * visitors: they are filtered out, and the Résultat section is hidden until at
 * least one real figure exists. Real figures render bold text-encre.
 * AA contrast guaranteed on papier background.
 *
 * @param {{ cas: object }} props
 */
export default function CaseStudy({ cas }) {
    if (!cas) return null;

    const isTodo = (val) => typeof val === 'string' && val.startsWith('// TODO');
    const resultats = (cas.resultats || []).filter((r) => !isTodo(r.valeur));

    return (
        <article className="max-w-3xl">
            {/* Secteur & date */}
            <div className="flex flex-wrap items-center gap-4 mb-8">
                {cas.secteur && (
                    <span className="font-mono text-legende uppercase tracking-widest text-acier">
                        {cas.secteur}
                    </span>
                )}
                {cas.cas_date && !isTodo(cas.cas_date) && (
                    <span className="font-mono text-legende text-acier">{cas.cas_date}</span>
                )}
            </div>

            {/* Problème */}
            {cas.probleme && (
                <section className="mb-10">
                    <SectionLabel number="01">PROBLÈME</SectionLabel>
                    <p className="mt-4 text-encre leading-relaxed">{cas.probleme}</p>
                </section>
            )}

            {/* Contrainte */}
            {cas.contrainte && (
                <section className="mb-10">
                    <SectionLabel number="02">CONTRAINTE</SectionLabel>
                    <p className="mt-4 text-encre leading-relaxed">{cas.contrainte}</p>
                </section>
            )}

            {/* Choix */}
            {cas.choix && (
                <section className="mb-10">
                    <SectionLabel number="03">CHOIX</SectionLabel>
                    <p className="mt-4 text-encre leading-relaxed">{cas.choix}</p>
                </section>
            )}

            {/* Résultats — masqués tant qu'aucun chiffre réel n'est fourni (§6) */}
            {resultats.length > 0 && (
                <section className="mb-10">
                    <SectionLabel number="04">RÉSULTAT</SectionLabel>
                    <dl className="mt-6 grid grid-cols-2 gap-8 sm:grid-cols-4">
                        {resultats.map((r, i) => (
                            <div key={i} className="flex flex-col gap-1">
                                <dt className="text-legende uppercase tracking-widest text-acier font-mono">
                                    {r.label}
                                </dt>
                                <dd>
                                    <span className="font-bold text-encre text-kodem-h2">
                                        {r.valeur}
                                    </span>
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>
            )}
        </article>
    );
}
