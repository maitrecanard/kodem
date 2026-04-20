import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';

export default function Home({ meta, prestations = [] }) {
    return (
        <PublicLayout meta={meta}>
            <section className="relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-sky-600" aria-hidden="true" />
                <div className="relative max-w-6xl mx-auto px-6 py-20 md:py-28 text-white">
                    <p className="uppercase tracking-widest text-xs text-indigo-200">Développement · Hébergement · Audits</p>
                    <h1 className="mt-3 text-4xl md:text-6xl font-bold leading-tight max-w-3xl">
                        Audit SEO et audit de sécurité automatisés, développement web et création de SaaS
                    </h1>
                    <p className="mt-6 text-lg text-indigo-100 max-w-2xl">
                        Kodem accompagne les entreprises ambitieuses : développement web sur-mesure, hébergement web managé,
                        audits SEO et de sécurité automatisés. Testez gratuitement votre site en ligne en 30 secondes.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Link
                            href="/audit"
                            onClick={() => trackClick('hero_cta_audit', { location: 'home_hero' })}
                            className="inline-flex items-center rounded-md bg-white text-indigo-700 px-5 py-3 font-semibold shadow-lg hover:bg-indigo-50"
                        >
                            Lancer un audit gratuit
                        </Link>
                        <Link
                            href="/prestations"
                            onClick={() => trackClick('hero_cta_services', { location: 'home_hero' })}
                            className="inline-flex items-center rounded-md border border-indigo-300/60 px-5 py-3 font-medium hover:bg-white/10"
                        >
                            Découvrir les prestations
                        </Link>
                    </div>
                    <dl className="mt-12 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl">
                        {[
                            ['100/100', 'Score de sécurité cible'],
                            ['24/7', 'Monitoring'],
                            ['< 30 s', 'Audit en ligne'],
                            ['SSR', 'SEO natif'],
                        ].map(([n, l]) => (
                            <div key={l}>
                                <dt className="text-3xl font-bold">{n}</dt>
                                <dd className="text-xs uppercase tracking-wide text-indigo-200 mt-1">{l}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-6 py-20">
                <div className="flex items-end justify-between mb-10">
                    <div>
                        <h2 className="text-3xl font-bold">Nos prestations</h2>
                        <p className="text-slate-600 mt-2">Des services packagés, automatisés et transparents.</p>
                    </div>
                    <Link href="/prestations" className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        Toutes les prestations →
                    </Link>
                </div>
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {prestations.map((p) => (
                        <article key={p.slug} className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
                            <h3 className="font-semibold text-lg">{p.title}</h3>
                            <p className="text-sm text-slate-500 mt-1">{p.tagline}</p>
                            <p className="mt-4 text-sm">{p.description}</p>
                            <p className="mt-4 text-indigo-700 font-medium text-sm">{p.price_label}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="bg-white border-y border-slate-200">
                <div className="max-w-6xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-10">
                    <div>
                        <h2 className="text-3xl font-bold">Un audit, deux dimensions</h2>
                        <p className="text-slate-600 mt-4">
                            Notre moteur d'audit en ligne analyse en même temps la performance SEO et la posture de sécurité
                            de votre site. Résultat en quelques secondes, rapport détaillé, note sur 100.
                        </p>
                        <ul className="mt-6 space-y-3 text-slate-700">
                            <li>• Balises title, meta description, Open Graph, H1</li>
                            <li>• robots.txt, sitemap.xml, temps de réponse</li>
                            <li>• HTTPS, HSTS, CSP, X-Frame-Options, Referrer-Policy</li>
                            <li>• Cookies, redirections, certificat TLS</li>
                        </ul>
                        <Link
                            href="/audit"
                            className="mt-8 inline-flex items-center rounded-md bg-indigo-600 text-white px-5 py-3 font-medium hover:bg-indigo-700"
                        >
                            Lancer mon audit maintenant
                        </Link>
                    </div>
                    <div className="rounded-xl bg-slate-900 text-slate-100 p-8 font-mono text-sm">
                        <div className="text-emerald-400">$ kodem audit https://exemple.fr</div>
                        <div className="mt-3 text-slate-300">→ SEO : 92/100</div>
                        <div className="text-slate-300">→ Sécurité : 98/100</div>
                        <div className="text-slate-300">→ Score global : 95/100</div>
                        <div className="mt-4 text-slate-500">Certificat TLS : OK</div>
                        <div className="text-slate-500">HSTS : activé (2 ans)</div>
                        <div className="text-slate-500">CSP : stricte</div>
                        <div className="text-slate-500">X-Frame-Options : DENY</div>
                        <div className="text-slate-500">meta description : 155 car.</div>
                    </div>
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-6 py-20 text-center">
                <h2 className="text-3xl font-bold">Prêt à passer à l'action&nbsp;?</h2>
                <p className="text-slate-600 mt-3 max-w-2xl mx-auto">
                    Obtenez un diagnostic SEO et sécurité en moins d'une minute, ou parlez à un expert pour
                    un projet de développement web, création de SaaS ou hébergement.
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <Link
                        href="/audit"
                        onClick={() => trackClick('home_final_cta_audit')}
                        className="rounded-md bg-indigo-600 text-white px-5 py-3 font-medium hover:bg-indigo-700"
                    >
                        Lancer un audit
                    </Link>
                    <Link
                        href="/contact"
                        onClick={() => trackClick('home_final_cta_contact')}
                        className="rounded-md border border-slate-300 px-5 py-3 font-medium hover:bg-slate-100"
                    >
                        Contacter l'équipe
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
