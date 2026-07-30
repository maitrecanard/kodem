import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';

export default function Services({ meta, prestations = [] }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                image="/images/banniere-kodem.webp"
                imageSources={[
                    { src: '/images/banniere-kodem.webp', width: 1006 },
                    { src: '/images/banniere-kodem-2x.webp', width: 2012 },
                ]}
                title="Nos prestations"
                cta={
                    <Link
                        href="/audit"
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Demander un audit
                    </Link>
                }
            />

            <section className="max-w-6xl mx-auto px-6 py-12">
                <SectionLabel className="mb-4">CATALOGUE</SectionLabel>
                <p className="animate-kodem-fade text-acier max-w-2xl mb-8">
                    Découvrez nos prestations pour la création de site internet, d'application web et
                    de logiciel sur-mesure, l'hébergement web managé et les audits SEO et de sécurité
                    automatisés.
                </p>
                <div className="kodem-reveal grid gap-6 md:grid-cols-2">
                    {prestations.map((p, index) => {
                        const num = String(index + 1).padStart(2, '0');
                        return (
                            <article key={p.slug} className="bg-white rounded-kodem border border-brume p-8 shadow-sm">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <span className="font-mono text-legende text-cobalt-600">{num}</span>
                                        <h2 className="text-kodem-h2 font-semibold mt-1">{p.title}</h2>
                                        <p className="text-acier text-sm mt-1">{p.tagline}</p>
                                    </div>
                                    <span className="inline-flex items-center rounded-full bg-cobalt-100 text-cobalt-700 px-3 py-1 text-xs font-mono whitespace-nowrap">
                                        {p.price_label}
                                    </span>
                                </div>
                                <p className="mt-4 text-encre">{p.description}</p>
                                <ul className="mt-4 space-y-2 text-sm text-encre">
                                    {p.features.map((f) => (
                                        <li key={f}>• {f}</li>
                                    ))}
                                </ul>
                                <div className="mt-6 flex items-center gap-4">
                                    <CodeButton
                                        href={
                                            p.cta_route === 'audit.create' ? '/audit'
                                            : p.cta_route === 'monitoring' ? '/monitoring'
                                            : '/contact'
                                        }
                                    >
                                        {p.cta}
                                    </CodeButton>
                                    {p.slug === 'hebergement-web' && (
                                        <Link href="/hebergement-web" className="text-sm text-cobalt-600 hover:text-cobalt-800 font-medium">
                                            En savoir plus →
                                        </Link>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </div>
            </section>
        </PublicLayout>
    );
}
