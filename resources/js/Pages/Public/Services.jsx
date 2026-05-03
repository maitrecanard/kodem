import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Services({ meta, prestations = [] }) {
    return (
        <PublicLayout meta={meta}>
            <section className="bg-gradient-to-b from-indigo-50 to-white">
                <div className="max-w-6xl mx-auto px-6 py-16">
                    <p className="uppercase tracking-widest text-xs text-indigo-600">Prestations</p>
                    <h1 className="mt-2 text-4xl font-bold">Nos services</h1>
                    <p className="mt-4 text-slate-600 max-w-2xl">
                        Développement web, création de SaaS, hébergement web et audits SEO et de sécurité automatisés.
                    </p>
                </div>
            </section>
            <section className="max-w-6xl mx-auto px-6 py-12 grid gap-6 md:grid-cols-2">
                {prestations.map((p) => (
                    <article key={p.slug} className="bg-white rounded-xl border border-slate-200 p-8 shadow-sm">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="font-semibold text-xl">{p.title}</h2>
                                <p className="text-slate-500 text-sm mt-1">{p.tagline}</p>
                            </div>
                            <span className="inline-flex items-center rounded-full bg-indigo-50 text-indigo-700 px-3 py-1 text-xs font-medium whitespace-nowrap">
                                {p.price_label}
                            </span>
                        </div>
                        <p className="mt-4 text-slate-700">{p.description}</p>
                        <ul className="mt-4 space-y-2 text-sm text-slate-700">
                            {p.features.map((f) => (
                                <li key={f}>• {f}</li>
                            ))}
                        </ul>
                        <div className="mt-6">
                            <Link
                                href={
                                    p.cta_route === 'audit.create' ? '/audit'
                                    : p.cta_route === 'monitoring' ? '/monitoring'
                                    : '/contact'
                                }
                                className="inline-flex rounded-md bg-indigo-600 text-white px-4 py-2 text-sm font-medium hover:bg-indigo-700"
                            >
                                {p.cta}
                            </Link>
                        </div>
                    </article>
                ))}
            </section>
        </PublicLayout>
    );
}
