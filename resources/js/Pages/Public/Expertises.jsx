import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';

const FAQ = [
    {
        q: 'Quelle est la différence entre un dispositif connecté sur site et une solution cloud générique ?',
        a: 'Un dispositif sur site fonctionne localement — pas de dépendance internet pendant l\'utilisation. KODEM assure le déploiement physique, la configuration sur le matériel du client et la supervision à distance. La logique s\'exécute là où elle doit s\'exécuter.',
    },
    {
        q: 'Sur quel matériel intervenez-vous ?',
        a: 'Raspberry Pi, mini-PC x86, bornes custom, écrans industriels. Le choix du matériel dépend des contraintes d\'environnement (chaleur, humidité, vandalisme) et du budget. KODEM conseille et déploie.',
    },
    {
        q: 'Le logiciel est-il maintenable après la livraison ?',
        a: 'Oui. KODEM assure les mises à jour, le monitoring et les interventions correctives. Le code source appartient au client.',
    },
];

export default function Expertises({ meta, positioning = {} }) {
    const capabilities = positioning.capabilities || [];

    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Capacités techniques — au service des dispositifs connectés"
                cta={
                    <Link
                        href="/contact"
                        onClick={() => trackClick('expertises_hero_contact')}
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Parler de votre projet
                    </Link>
                }
            />

            {/* Answer-first intro */}
            <section className="max-w-6xl mx-auto px-6 pt-16 pb-8">
                <SectionLabel>CAPACITÉS</SectionLabel>
                <p className="mt-4 max-w-2xl text-lg text-acier">
                    KODEM conçoit et déploie des dispositifs logiciels sur site.
                    Le développement, l'hébergement, le SEO et la sécurité sont des moyens — pas le produit.
                    Ils entrent en jeu là où le projet l'exige.
                </p>
            </section>

            {/* Capabilities grid */}
            {capabilities.length > 0 && (
                <section className="max-w-6xl mx-auto px-6 pb-20">
                    <div className="grid gap-6 md:grid-cols-2">
                        {capabilities.map((cap, i) => (
                            <div
                                key={i}
                                className="bg-white rounded-kodem border border-brume p-6 shadow-sm"
                            >
                                <SectionLabel number={String(i + 1).padStart(2, '0')}>
                                    {cap.titre.toUpperCase()}
                                </SectionLabel>
                                <p className="mt-4 text-acier leading-relaxed">{cap.role_support}</p>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Secteurs */}
            {positioning.secteurs?.length > 0 && (
                <section className="bg-white border-y border-brume">
                    <div className="max-w-6xl mx-auto px-6 py-16">
                        <SectionLabel>SECTEURS</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">
                            Secteurs à présence physique
                        </h2>
                        <p className="mt-4 text-acier max-w-xl">
                            Les dispositifs connectés ont du sens là où le public est physiquement présent.
                        </p>
                        <ul className="mt-8 flex flex-wrap gap-3">
                            {positioning.secteurs.map((s, i) => (
                                <li
                                    key={i}
                                    className="font-mono text-sm px-4 py-2 rounded-kodem border border-brume bg-papier text-encre"
                                >
                                    {s}
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}

            {/* FAQ */}
            <section className="max-w-6xl mx-auto px-6 py-20">
                <SectionLabel>FAQ</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">Questions fréquentes</h2>
                <dl className="mt-8 space-y-8 max-w-3xl">
                    {FAQ.map((item, i) => (
                        <div key={i} className="border-b border-brume pb-8 last:border-0 last:pb-0">
                            <dt className="font-semibold text-encre mb-3">{item.q}</dt>
                            <dd className="text-acier leading-relaxed">{item.a}</dd>
                        </div>
                    ))}
                </dl>
            </section>

            {/* CTA */}
            <section className="max-w-6xl mx-auto px-6 pb-20 text-center">
                <SectionLabel className="justify-center">PROJET</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold">Un dispositif à concevoir ou à reprendre ?</h2>
                <p className="mt-4 text-acier max-w-xl mx-auto">
                    Décrivez votre contexte — matériel, secteur, contraintes — et nous vous répondons sous 48 h.
                </p>
                <div className="mt-8">
                    <CodeButton
                        href="/contact"
                        onClick={() => trackClick('expertises_cta_contact')}
                    >
                        contacter_kodem()
                    </CodeButton>
                </div>
            </section>
        </PublicLayout>
    );
}
