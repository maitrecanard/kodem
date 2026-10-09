import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';
import ContactBlockMono from '@/Components/ContactBlockMono';

const WORK_MODES = [
    {
        label: 'À DISTANCE',
        texte: 'Échanges en visioconférence, accès sécurisés à vos environnements, livraisons suivies en préproduction avant la production.',
    },
    {
        label: 'SUR SITE SI BESOIN',
        texte: 'Quand une présence est utile, déplacement le jour même en Nouvelle-Aquitaine, sous 48 h partout ailleurs en France.',
    },
    {
        label: 'INTERLOCUTEUR UNIQUE',
        texte: 'La même personne du cadrage à la mise en production, puis au suivi. Pas de ticket, pas de transfert.',
    },
];

const DELIVERY_STEPS = [
    {
        numero: '01',
        label: 'CADRAGE',
        texte: "On part d'une tâche manuelle précise : qui la fait, combien de fois, avec quels logiciels, où elle casse. Le cadrage fixe ce que l'outil supprime et ce qu'il ne touche pas.",
    },
    {
        numero: '02',
        label: 'CONCEPTION ET TESTS',
        texte: "Base de données, règles métier et intégrations sont écrites avec des tests automatisés. Chaque version est vérifiée par l'intégration continue avant d'arriver chez vous.",
    },
    {
        numero: '03',
        label: 'MISE EN PRODUCTION ET SUIVI',
        texte: 'Déploiement automatisé en préproduction puis en production. Après la livraison, corrections et évolutions passent par le même interlocuteur ; le code source vous appartient.',
    },
];

export default function ZoneIntervention({ meta, ctaFinal }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Méthode de travail : à distance, sur site si besoin"
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
                <SectionLabel>MÉTHODE</SectionLabel>
                <h2 className="mt-4 text-kodem-h1 font-bold max-w-2xl">
                    Conception et mise en production à distance, partout en France.
                </h2>
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    Basé à Poitiers, KODEM conçoit, teste et met en production vos outils à distance. Un déplacement
                    sur site reste possible quand le projet l'exige : atelier de cadrage, observation d'une tâche sur
                    le terrain, mise en service accompagnée.
                </p>
            </section>

            <section className="max-w-6xl mx-auto px-6 pb-16">
                <div className="kodem-reveal-grid kodem-reveal-grid--3 grid gap-6 md:grid-cols-3">
                    {WORK_MODES.map((mode) => (
                        <div key={mode.label} className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                            <SectionLabel>{mode.label}</SectionLabel>
                            <p className="mt-4 text-acier text-sm leading-relaxed">{mode.texte}</p>
                        </div>
                    ))}
                </div>
            </section>

            <DeliverySteps />

            <MethodContact texte={ctaFinal.texte} />
        </PublicLayout>
    );
}

function DeliverySteps() {
    return (
        <section className="bg-white border-y border-brume">
            <div className="max-w-6xl mx-auto px-6 py-16">
                <SectionLabel>MODÈLE DE LIVRAISON</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">Comment un outil passe en production</h2>
                <div className="kodem-reveal-grid kodem-reveal-grid--3 mt-10 grid gap-8 md:grid-cols-3">
                    {DELIVERY_STEPS.map((step) => (
                        <div key={step.label}>
                            <SectionLabel number={step.numero}>{step.label}</SectionLabel>
                            <p className="mt-3 text-acier text-sm leading-relaxed">{step.texte}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function MethodContact({ texte }) {
    return (
        <section className="kodem-reveal max-w-6xl mx-auto px-6 py-20">
            <div className="grid md:grid-cols-2 gap-12 items-start">
                <div>
                    <SectionLabel>CONTACT</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Une tâche à supprimer ?</h2>
                    <p className="mt-4 text-acier leading-relaxed">{texte}</p>
                    <div className="mt-8">
                        <CodeButton href="/contact" onClick={() => trackClick('zone_cta_contact')}>
                            contacter_kodem()
                        </CodeButton>
                    </div>
                </div>
                {/* NAP : graphie strictement identique au PostalAddress du JSON-LD */}
                <ContactBlockMono area="À distance partout en France, sur site si besoin" />
            </div>
        </section>
    );
}
