import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import SectorCards from '@/Components/SectorCards';
import FinalCallToAction from '@/Components/FinalCallToAction';

export default function Expertises({ meta, positioning }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Ce que KODEM automatise"
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
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    KODEM conçoit des outils métier sur mesure qui suppriment les tâches manuelles.
                    Quatre familles de tâches, un même socle technique pour les automatiser.
                </p>
            </section>

            <CapabilitiesGrid capabilities={positioning.capabilities} />

            {/* Socle technique : ce qui fait tenir un outil métier en production */}
            <TechnicalFoundation socle={positioning.socle_technique} />

            {/* Secteurs */}
            <section className="bg-white border-y border-brume">
                <div className="max-w-6xl mx-auto px-6 py-16">
                    <div className="kodem-reveal">
                        <SectionLabel>SECTEURS</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">Pour qui</h2>
                    </div>
                    <SectorCards secteurs={positioning.secteurs} />
                </div>
            </section>

            <FaqList faq={positioning.faq} />

            <FinalCallToAction
                ctaFinal={positioning.cta_final}
                trackingEvent="expertises_cta_contact"
                className="pb-20"
            />
        </PublicLayout>
    );
}

function CapabilitiesGrid({ capabilities }) {
    return (
        <section className="max-w-6xl mx-auto px-6 pb-20">
            <div className="kodem-reveal-grid kodem-reveal-grid--2 grid gap-6 md:grid-cols-2">
                {capabilities.map((cap, i) => (
                    <div key={cap.titre} className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                        <SectionLabel number={String(i + 1).padStart(2, '0')}>{cap.titre.toUpperCase()}</SectionLabel>
                        <p className="mt-4 text-acier leading-relaxed">{cap.role_support}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function TechnicalFoundation({ socle }) {
    return (
        <section className="max-w-6xl mx-auto px-6 pb-20">
            <SectionLabel>SOCLE TECHNIQUE</SectionLabel>
            <p className="mt-4 max-w-2xl text-acier leading-relaxed">{socle.intro}</p>
            <div className="kodem-reveal-grid kodem-reveal-grid--2 mt-8 grid gap-6 md:grid-cols-2">
                {socle.elements.map((el) => (
                    <div key={el.titre} className="border-l-2 border-cobalt-600 pl-5">
                        <h3 className="font-mono text-sm uppercase tracking-widest text-encre">{el.titre}</h3>
                        <p className="mt-2 text-acier leading-relaxed">{el.detail}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function FaqList({ faq }) {
    return (
        <section className="kodem-reveal max-w-6xl mx-auto px-6 py-20">
            <SectionLabel>FAQ</SectionLabel>
            <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">Questions fréquentes</h2>
            <dl className="mt-8 space-y-8 max-w-3xl">
                {faq.map((item) => (
                    <div key={item.question} className="border-b border-brume pb-8 last:border-0 last:pb-0">
                        <dt className="font-semibold text-encre mb-3">{item.question}</dt>
                        <dd className="text-acier leading-relaxed">{item.reponse}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
