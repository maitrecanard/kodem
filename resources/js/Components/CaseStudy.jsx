import SectionLabel from '@/Components/SectionLabel';

/**
 * CaseStudy — template §4 charte KODEM.
 * Renders a case study in order: problème → contrainte → choix → résultats.
 * A valeur starting with "// TODO" renders as a visible blocker in font-mono text-cobalt-600.
 * A real figure renders bold text-encre.
 * AA contrast guaranteed on papier background.
 *
 * @param {{ cas: object }} props
 */
export default function CaseStudy({ cas }) {
    if (!cas) return null;

    const isTodo = (val) => typeof val === 'string' && val.startsWith('// TODO');

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

            {/* Résultats */}
            {cas.resultats?.length > 0 && (
                <section className="mb-10">
                    <SectionLabel number="04">RÉSULTAT</SectionLabel>
                    <dl className="mt-6 grid grid-cols-2 gap-8 sm:grid-cols-4">
                        {cas.resultats.map((r, i) => (
                            <div key={i} className="flex flex-col gap-1">
                                <dt className="text-legende uppercase tracking-widest text-acier font-mono">
                                    {r.label}
                                </dt>
                                <dd>
                                    {isTodo(r.valeur) ? (
                                        <span className="font-mono text-cobalt-600 text-sm" title="Donnée réelle manquante">
                                            {r.valeur}
                                        </span>
                                    ) : (
                                        <span className="font-bold text-encre text-kodem-h2">
                                            {r.valeur}
                                        </span>
                                    )}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>
            )}
        </article>
    );
}
