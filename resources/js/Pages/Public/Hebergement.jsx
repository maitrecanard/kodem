import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';

export default function Hebergement({ meta, prestation }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Hébergement web managé"
                cta={
                    <Link
                        href="/contact"
                        onClick={() => trackClick('hebergement_hero_devis')}
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Demander un devis
                    </Link>
                }
            />

            <section className="max-w-6xl mx-auto px-6 pt-16">
                <SectionLabel>HÉBERGEMENT</SectionLabel>
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    L'hébergement des applications conçues par KODEM : TLS, sauvegardes chiffrées,
                    supervision et correctifs. Aucune gestion serveur de votre côté.
                </p>
            </section>

            {prestation?.features?.length > 0 && (
                <section className="max-w-6xl mx-auto px-6 pb-20 pt-12">
                    <SectionLabel number="01">FONCTIONNALITÉS</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Ce qui est inclus</h2>
                    <ul className="kodem-reveal-grid kodem-reveal-grid--2 mt-8 grid md:grid-cols-2 gap-4">
                        {prestation.features.map((f) => (
                            <li key={f} className="flex items-start gap-3 bg-white rounded-kodem border border-brume p-5 shadow-sm">
                                <span className="font-mono text-cobalt-400 mt-0.5">→</span>
                                <span className="text-encre">{f}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section className="kodem-reveal max-w-6xl mx-auto px-6 py-20 text-center">
                <SectionLabel number="02" className="justify-center">TARIF</SectionLabel>
                {prestation?.price_label && (
                    <p className="mt-2 font-mono text-cobalt-600 text-sm">{prestation.price_label}</p>
                )}
                <h2 className="mt-3 text-kodem-h1 font-bold">Prêt à héberger votre projet ?</h2>
                <p className="mt-4 text-acier max-w-2xl mx-auto">
                    Contactez-nous pour un devis personnalisé selon les ressources dont votre application a besoin.
                </p>
                <div className="mt-8">
                    <CodeButton
                        href="/contact"
                        onClick={() => trackClick('hebergement_cta_devis')}
                    >
                        demander_un_devis()
                    </CodeButton>
                </div>
            </section>
        </PublicLayout>
    );
}
