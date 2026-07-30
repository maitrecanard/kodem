import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';
import CaseImage from '@/Components/CaseImage';
import { TestimonialSection } from '@/Components/Testimonial';

// Un chiffre encore en « // TODO » (donnée réelle §6 non fournie) n'est jamais
// affiché sur la page phare : on ne montre pas une preuve qu'on n'a pas.
const isTodo = (v) => typeof v === 'string' && v.startsWith('// TODO');

export default function Home({ meta, positioning = {}, cases = [], testimonials = [] }) {
    const proofPoints = (positioning.proof_points || []).filter((p) => !isTodo(p.valeur));
    const secteurs = positioning.secteurs || [];
    const capabilities = positioning.capabilities || [];

    return (
        <PublicLayout meta={meta}>
            <Banner
                image="/images/banniere-kodem.webp"
                imageSources={[
                    { src: '/images/banniere-kodem.webp', width: 1006 },
                    { src: '/images/banniere-kodem-2x.webp', width: 2012 },
                    { src: '/images/banniere-kodem-3x.webp', width: 3000 },
                ]}
                title={positioning.hero_title || 'Dispositifs connectés sur site — conçus, déployés, sécurisés.'}
                cta={
                    <>
                        <Link
                            href={positioning.cta_primary?.href || '/contact'}
                            onClick={() => trackClick('hero_cta_contact', { location: 'home_hero' })}
                            className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium transition-colors duration-[var(--kodem-dur-2)] active:translate-y-px hover:bg-cobalt-700"
                        >
                            {positioning.cta_primary?.label || 'Parler de votre projet'}
                        </Link>
                        <Link
                            href={positioning.cta_secondary?.href || '/realisations'}
                            onClick={() => trackClick('hero_cta_realisations', { location: 'home_hero' })}
                            className="inline-flex items-center rounded-kodem border border-white/40 px-5 py-3 text-white font-medium transition-colors duration-[var(--kodem-dur-2)] active:translate-y-px hover:bg-white/10"
                        >
                            {positioning.cta_secondary?.label || 'Voir les réalisations'}
                        </Link>
                    </>
                }
            />

            {/* Positionnement niche — copy issu de content/positioning.json (§0) */}
            <section className="max-w-6xl mx-auto px-6 py-16">
                <div className="animate-kodem-rise max-w-3xl">
                    <SectionLabel>KODEM</SectionLabel>
                    {positioning.hero_baseline && (
                        <p className="mt-4 text-lg text-encre">{positioning.hero_baseline}</p>
                    )}
                    {positioning.value_prop && (
                        <p className="mt-4 text-acier">{positioning.value_prop}</p>
                    )}
                </div>

                {proofPoints.length > 0 && (
                    <dl className="kodem-reveal mt-12 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl">
                        {proofPoints.map((p) => (
                            <div key={p.label}>
                                <dt className="text-kodem-h1 font-bold text-encre">{p.valeur}</dt>
                                <dd className="text-legende uppercase text-acier mt-1">{p.label}</dd>
                            </div>
                        ))}
                    </dl>
                )}
            </section>

            {/* Secteurs cibles */}
            {secteurs.length > 0 && (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 pb-8">
                    <SectionLabel number="01">SECTEURS</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Là où un dispositif doit tenir sur site</h2>
                    <ul className="mt-6 flex flex-wrap gap-3">
                        {secteurs.map((s) => (
                            <li
                                key={s}
                                className="rounded-kodem border border-brume bg-white px-4 py-2 font-mono text-sm text-encre"
                            >
                                {s}
                            </li>
                        ))}
                    </ul>
                    {positioning.ancrage_local && (
                        <p className="mt-6 font-mono text-legende text-acier">// {positioning.ancrage_local}</p>
                    )}
                </section>
            )}

            {/* Réalisations — teaser des cas */}
            {cases.length > 0 && (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 py-16">
                    <div className="flex items-end justify-between mb-10">
                        <div>
                            <SectionLabel number="02">RÉALISATIONS</SectionLabel>
                            <h2 className="mt-3 text-kodem-h1 font-bold">Cas déployés sur le terrain</h2>
                            <p className="text-acier mt-2">
                                Problème réel, contrainte technique, choix justifié, résultat mesuré.
                            </p>
                        </div>
                        <Link
                            href="/realisations"
                            className="text-cobalt-600 hover:text-cobalt-800 text-sm font-medium"
                        >
                            Toutes les réalisations →
                        </Link>
                    </div>
                    <div className="grid gap-6 md:grid-cols-2">
                        {cases.map((cas) => (
                            <article
                                key={cas.slug}
                                className="kodem-card bg-white rounded-kodem border border-brume p-6 shadow-sm hover:shadow-md flex flex-col gap-4"
                            >
                                <CaseImage cas={cas} variant="card" />

                                <SectionLabel className="mb-0">{cas.secteur?.toUpperCase()}</SectionLabel>
                                <h3 className="text-kodem-h2 font-bold text-encre">{cas.titre}</h3>
                                <p className="text-sm text-acier flex-1">{cas.resume}</p>
                                <div>
                                    <CodeButton
                                        href={`/realisations/${cas.slug}`}
                                        onClick={() => trackClick('home_case_cta', { slug: cas.slug })}
                                    >
                                        lire_le_cas()
                                    </CodeButton>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            )}

            {/* Témoignages (§5/§9-6 : sur l'accueil ; section masquée si aucune donnée réelle) */}
            {testimonials.length > 0 && (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 py-16">
                    <SectionLabel number="03">TÉMOIGNAGES</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Ce que disent les clients</h2>
                    <TestimonialSection items={testimonials} />
                </section>
            )}

            {/* Capacités en support (§8 : jamais en façade) */}
            {capabilities.length > 0 && (
                <section className="kodem-reveal bg-white border-y border-brume">
                    <div className="max-w-6xl mx-auto px-6 py-16">
                        <SectionLabel number="04">CAPACITÉS EN SUPPORT</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold max-w-3xl">
                            Quatre capacités au service du dispositif
                        </h2>
                        <p className="mt-4 text-acier max-w-2xl">
                            Développement, hébergement, visibilité et sécurité ne sont pas le produit :
                            ce sont les moyens qui font tenir un dispositif connecté dans la durée.
                        </p>
                        <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {capabilities.map((c) => (
                                <div key={c.titre} className="rounded-kodem border border-brume p-6">
                                    <h3 className="text-kodem-h2 font-semibold text-encre">{c.titre}</h3>
                                    <p className="mt-2 text-sm text-acier">{c.role_support}</p>
                                </div>
                            ))}
                        </div>
                        <div className="mt-8">
                            <Link
                                href="/expertises"
                                className="inline-flex items-center rounded-kodem border border-cobalt-600 text-cobalt-600 px-5 py-3 font-medium hover:bg-cobalt-50"
                            >
                                Voir les expertises →
                            </Link>
                        </div>
                    </div>
                </section>
            )}

            {/* CTA final */}
            <section className="kodem-reveal max-w-6xl mx-auto px-6 py-20 text-center">
                <SectionLabel number="05" className="justify-center">PROJET</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold">Un dispositif à concevoir ou déployer&nbsp;?</h2>
                <p className="text-acier mt-3 max-w-2xl mx-auto">
                    Décrivez votre besoin — matériel, lieu, contraintes. Réponse sous 48 h.
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <CodeButton href="/contact" onClick={() => trackClick('home_final_cta_contact')}>
                        contacter_kodem()
                    </CodeButton>
                    <CodeButton
                        href="/realisations"
                        onClick={() => trackClick('home_final_cta_realisations')}
                        className="bg-cobalt-900 hover:bg-cobalt-700"
                    >
                        voir_les_cas()
                    </CodeButton>
                </div>
                <p className="mt-6 font-mono text-legende text-acier">
                    // réponse sous 48 h · devis gratuit · interlocuteur unique
                </p>
            </section>
        </PublicLayout>
    );
}
