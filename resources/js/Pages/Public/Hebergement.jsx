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
                <p className="mt-4 max-w-2xl text-lg text-acier">
                    Un hébergement web sécurisé, sauvegardé et monitoré en continu, pour vos sites
                    internet, applications web et SaaS. Aucune gestion serveur de votre côté.
                </p>
            </section>

            {prestation?.features?.length > 0 && (
                <section className="max-w-6xl mx-auto px-6 pb-20 pt-12">
                    <SectionLabel number="01">FONCTIONNALITÉS</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Ce qui est inclus</h2>
                    <ul className="mt-8 grid md:grid-cols-2 gap-4">
                        {prestation.features.map((f) => (
                            <li key={f} className="flex items-start gap-3 bg-white rounded-kodem border border-brume p-5 shadow-sm">
                                <span className="font-mono text-cobalt-400 mt-0.5">→</span>
                                <span className="text-encre">{f}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section className="bg-white border-y border-brume">
                <div className="max-w-6xl mx-auto px-6 py-20">
                    <SectionLabel number="02">PROJETS</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Pour quels projets ?</h2>
                    <p className="mt-4 text-acier max-w-2xl">
                        Notre hébergement web managé s'adapte à tous les types de projets web, des sites vitrines aux applications complexes.
                    </p>
                    <div className="mt-8 grid md:grid-cols-3 gap-6">
                        <div className="rounded-kodem border border-brume p-6">
                            <h3 className="text-kodem-h2 font-semibold">Site internet</h3>
                            <p className="mt-3 text-sm text-acier">
                                Hébergement de sites internet vitrine et e-commerce : performances optimisées, TLS automatique et sauvegardes quotidiennes.
                            </p>
                        </div>
                        <div className="rounded-kodem border border-brume p-6">
                            <h3 className="text-kodem-h2 font-semibold">Application web</h3>
                            <p className="mt-3 text-sm text-acier">
                                Hébergement d'applications web Laravel, React et API REST : monitoring 24/7, WAF et déploiements sans interruption.
                            </p>
                        </div>
                        <div className="rounded-kodem border border-brume p-6">
                            <h3 className="text-kodem-h2 font-semibold">SaaS</h3>
                            <p className="mt-3 text-sm text-acier">
                                Infrastructure dédiée pour vos logiciels SaaS : haute disponibilité, sauvegardes chiffrées et protection DDoS incluse.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-6 py-20 text-center">
                <SectionLabel number="03" className="justify-center">TARIF</SectionLabel>
                {prestation?.price_label && (
                    <p className="mt-2 font-mono text-cobalt-600 text-sm">{prestation.price_label}</p>
                )}
                <h2 className="mt-3 text-kodem-h1 font-bold">Prêt à héberger votre projet ?</h2>
                <p className="mt-4 text-acier max-w-2xl mx-auto">
                    Contactez-nous pour un devis personnalisé selon vos besoins en ressources, trafic et niveau de disponibilité attendu.
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
