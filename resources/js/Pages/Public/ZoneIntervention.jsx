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
                image="/images/banniere-kodem.webp"
                imageSources={[
                    { src: '/images/banniere-kodem.webp', width: 1006 },
                    { src: '/images/banniere-kodem-2x.webp', width: 2012 },
                ]}
                title="Zone d'intervention — partout en France"
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
                <SectionLabel>COUVERTURE</SectionLabel>
                <h2 className="mt-4 text-kodem-h1 font-bold max-w-2xl">
                    KODEM intervient sur site partout en France.
                </h2>
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    Basé à Poitiers, KODEM se déplace pour installer et configurer les dispositifs directement chez le client,
                    en Nouvelle-Aquitaine comme dans le reste de la France. L'installation physique ne se délègue pas
                    à distance — c'est ce qui distingue un intégrateur de terrain d'une agence web généraliste.
                </p>
            </section>

            {/* Advantage of local presence */}
            <section className="max-w-6xl mx-auto px-6 pb-16">
                <div className="kodem-reveal-grid kodem-reveal-grid--3 grid gap-6 md:grid-cols-3">
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
                            Diagnostic à distance immédiat en cas d'incident. Déplacement sur site le jour même
                            en Nouvelle-Aquitaine, sous 48 h partout ailleurs en France.
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

            {/* Modèle d'intervention */}
            <section className="bg-white border-y border-brume">
                <div className="max-w-6xl mx-auto px-6 py-16">
                    <SectionLabel>MODÈLE D'INTERVENTION</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">Comment KODEM couvre la France</h2>
                    <div className="kodem-reveal-grid kodem-reveal-grid--3 mt-10 grid gap-8 md:grid-cols-3">
                        <div>
                            <SectionLabel number="01">PRÉPARATION À DISTANCE</SectionLabel>
                            <p className="mt-3 text-acier text-sm leading-relaxed">
                                L'image système, la configuration et les tests sont faits en atelier sur du matériel
                                identique. Ce qui arrive sur site est déjà validé.
                            </p>
                        </div>
                        <div>
                            <SectionLabel number="02">POSE PLANIFIÉE</SectionLabel>
                            <p className="mt-3 text-acier text-sm leading-relaxed">
                                L'installation se fait en une visite datée avec le client, où que soit le site.
                                Le déplacement est intégré au devis, pas facturé après coup.
                            </p>
                        </div>
                        <div>
                            <SectionLabel number="03">SUPERVISION ET RETOUR</SectionLabel>
                            <p className="mt-3 text-acier text-sm leading-relaxed">
                                Le dispositif remonte son état. La plupart des incidents se traitent à distance ;
                                quand la main sur le matériel est nécessaire, le délai est celui annoncé au contrat.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Secteurs desservis */}
            {positioning.secteurs?.length > 0 && (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 py-16">
                    <SectionLabel>SECTEURS</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">
                        Secteurs desservis
                    </h2>
                    <ul className="mt-8 flex flex-wrap gap-3">
                        {positioning.secteurs.map((s, i) => (
                            <li
                                key={i}
                                className="font-mono text-sm px-4 py-2 rounded-kodem border border-brume bg-white text-encre"
                            >
                                {s}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {/* NAP + CTA */}
            <section className="kodem-reveal max-w-6xl mx-auto px-6 py-20">
                <div className="grid md:grid-cols-2 gap-12 items-start">
                    <div>
                        <SectionLabel>CONTACT</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold">
                            Un projet à déployer sur site ?
                        </h2>
                        <p className="mt-4 text-acier leading-relaxed">
                            Décrivez votre dispositif, votre secteur et vos contraintes. KODEM vous répond sous 48 h
                            avec une première analyse. Précisez la ville : elle détermine le délai de déplacement.
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
                    {/* NAP — graphie strictement identique au PostalAddress du JSON-LD */}
                    <ContactBlockMono
                        area="Intervention partout en France"
                    />
                </div>
            </section>
        </PublicLayout>
    );
}
