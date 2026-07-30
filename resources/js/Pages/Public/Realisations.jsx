import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';
import CaseImage from '@/Components/CaseImage';

export default function Realisations({ meta, positioning = {}, cases = [] }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                image="/images/banniere-kodem.webp"
                imageSources={[
                    { src: '/images/banniere-kodem.webp', width: 1006 },
                    { src: '/images/banniere-kodem-2x.webp', width: 2012 },
                ]}
                title={positioning.hero_title || 'Réalisations — dispositifs connectés sur site'}
                cta={
                    <Link
                        href="/contact"
                        onClick={() => trackClick('realisations_hero_contact')}
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Parler de votre projet
                    </Link>
                }
            />

            {/* Answer-first intro */}
            <section className="max-w-6xl mx-auto px-6 pt-16 pb-8">
                <SectionLabel>RÉALISATIONS</SectionLabel>
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    Deux cas concrets : un photomaton autonome et un réseau d'écrans piloté à distance.
                    Chaque projet suit le même schéma — problème réel, contrainte technique, choix justifié, résultat mesuré.
                </p>
            </section>

            {/* Case cards grid */}
            {cases.length > 0 ? (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 pb-20">
                    <div className="grid gap-6 md:grid-cols-2">
                        {cases.map((cas) => (
                            <article
                                key={cas.slug}
                                className="kodem-card bg-white rounded-kodem border border-brume p-6 shadow-sm hover:shadow-md flex flex-col gap-4"
                            >
                                <CaseImage cas={cas} variant="card" />

                                <div className="flex items-center justify-between">
                                    <SectionLabel className="mb-0">{cas.secteur?.toUpperCase()}</SectionLabel>
                                    {cas.cas_date && !cas.cas_date.startsWith('// TODO') && (
                                        <span className="font-mono text-legende text-acier">{cas.cas_date}</span>
                                    )}
                                </div>

                                <h2 className="text-kodem-h2 font-bold text-encre">{cas.titre}</h2>

                                <p className="text-sm text-acier flex-1">{cas.resume}</p>

                                <div className="mt-2">
                                    <CodeButton
                                        href={`/realisations/${cas.slug}`}
                                        onClick={() => trackClick('realisations_card_cta', { slug: cas.slug })}
                                    >
                                        lire_le_cas()
                                    </CodeButton>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            ) : (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 pb-20">
                    <p className="text-acier">Aucune réalisation disponible pour le moment.</p>
                </section>
            )}

            {/* CTA contact */}
            <section className="kodem-reveal bg-white border-y border-brume">
                <div className="max-w-6xl mx-auto px-6 py-16 text-center">
                    <SectionLabel className="justify-center">PROJET</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Un dispositif similaire à déployer ?</h2>
                    <p className="mt-4 text-acier max-w-xl mx-auto">
                        Décrivez votre besoin — matériel, lieu, contraintes — et nous vous répondons sous 48 h.
                    </p>
                    <div className="mt-8">
                        <CodeButton
                            href="/contact"
                            onClick={() => trackClick('realisations_cta_contact')}
                        >
                            contacter_kodem()
                        </CodeButton>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
