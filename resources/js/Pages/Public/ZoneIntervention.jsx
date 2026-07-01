import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';
import ContactBlockMono from '@/Components/ContactBlockMono';

export default function ZoneIntervention({ meta, positioning = {} }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Zone d'intervention — Nouvelle-Aquitaine et Poitiers"
                cta={
                    <Link
                        href="/contact"
                        onClick={() => trackClick('zone_hero_contact')}
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Parler de votre projet
                    </Link>
                }
            />

            {/* Answer-first intro */}
            <section className="max-w-6xl mx-auto px-6 pt-16 pb-8">
                <SectionLabel>ANCRAGE LOCAL</SectionLabel>
                <h2 className="mt-4 text-kodem-h1 font-bold max-w-2xl">
                    KODEM intervient sur site en Nouvelle-Aquitaine.
                </h2>
                <p className="mt-4 max-w-2xl text-lg text-acier">
                    Basé à Poitiers, KODEM déploie et configure les dispositifs directement chez le client.
                    L'installation physique ne se délègue pas à distance — c'est précisément ce qui distingue
                    un prestataire local d'une agence web généraliste.
                </p>
            </section>

            {/* Advantage of local presence */}
            <section className="max-w-6xl mx-auto px-6 pb-16">
                <div className="grid gap-6 md:grid-cols-3">
                    <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                        <SectionLabel>DÉPLOIEMENT</SectionLabel>
                        <p className="mt-4 text-acier text-sm leading-relaxed">
                            Configuration sur le matériel réel, dans l'environnement réel — réseau, lumière,
                            flux de personnes. Pas de surprises le jour de l'ouverture.
                        </p>
                    </div>
                    <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                        <SectionLabel>RÉACTIVITÉ</SectionLabel>
                        <p className="mt-4 text-acier text-sm leading-relaxed">
                            En cas d'incident, un déplacement sur site est possible le jour même en Nouvelle-Aquitaine.
                            Les systèmes critiques le méritent.
                        </p>
                    </div>
                    <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                        <SectionLabel>INTERLOCUTEUR UNIQUE</SectionLabel>
                        <p className="mt-4 text-acier text-sm leading-relaxed">
                            Même personne du cahier des charges à la maintenance. Pas de ticket, pas de transfert.
                        </p>
                    </div>
                </div>
            </section>

            {/* Secteurs locaux */}
            {positioning.secteurs?.length > 0 && (
                <section className="bg-white border-y border-brume">
                    <div className="max-w-6xl mx-auto px-6 py-16">
                        <SectionLabel>SECTEURS</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">
                            Secteurs desservis en Nouvelle-Aquitaine
                        </h2>
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

            {/* NAP + CTA */}
            <section className="max-w-6xl mx-auto px-6 py-20">
                <div className="grid md:grid-cols-2 gap-12 items-start">
                    <div>
                        <SectionLabel>CONTACT</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold">
                            Un projet en Nouvelle-Aquitaine ?
                        </h2>
                        <p className="mt-4 text-acier leading-relaxed">
                            Décrivez votre dispositif, votre secteur et vos contraintes.
                            KODEM vous répond sous 48 h avec une première analyse.
                        </p>
                        <div className="mt-8">
                            <CodeButton
                                href="/contact"
                                onClick={() => trackClick('zone_cta_contact')}
                            >
                                contacter_kodem()
                            </CodeButton>
                        </div>
                    </div>
                    {/* NAP — cohérence critique pour le SEO local */}
                    <ContactBlockMono
                        address="Poitiers, Nouvelle-Aquitaine"
                    />
                </div>
            </section>
        </PublicLayout>
    );
}
