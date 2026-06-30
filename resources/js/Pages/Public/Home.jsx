import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';

export default function Home({ meta, prestations = [] }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Concevoir, héberger, sécuriser."
                cta={
                    <Link
                        href="/audit"
                        onClick={() => trackClick('hero_cta_audit', { location: 'home_hero' })}
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Demander un audit
                    </Link>
                }
            />

            <section className="max-w-6xl mx-auto px-6 py-16">
                <div className="max-w-3xl">
                    <SectionLabel>KODEM</SectionLabel>
                    <p className="mt-4 text-lg text-acier">
                        Kodem accompagne les entreprises ambitieuses : développement web sur-mesure,
                        hébergement web managé, audits SEO et de sécurité automatisés. Testez
                        gratuitement votre site en ligne en 30 secondes.
                    </p>
                    <div className="mt-6">
                        <CodeButton
                            href="/prestations"
                            onClick={() => trackClick('hero_cta_services', { location: 'home_hero' })}
                        >
                            voir_prestations()
                        </CodeButton>
                    </div>
                </div>
                <dl className="mt-12 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl">
                    {[
                        ['100/100', 'Score de sécurité cible'],
                        ['24/7', 'Monitoring'],
                        ['< 30 s', 'Audit en ligne'],
                        ['SSR', 'SEO natif'],
                    ].map(([n, l]) => (
                        <div key={l}>
                            <dt className="text-kodem-h1 font-bold text-encre">{n}</dt>
                            <dd className="text-legende uppercase text-acier mt-1">{l}</dd>
                        </div>
                    ))}
                </dl>
            </section>

            <section className="max-w-6xl mx-auto px-6 py-20">
                <div className="flex items-end justify-between mb-10">
                    <div>
                        <SectionLabel number="01">PRESTATIONS</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold">Nos prestations</h2>
                        <p className="text-acier mt-2">Des services packagés, automatisés et transparents.</p>
                    </div>
                    <Link href="/prestations" className="text-cobalt-600 hover:text-cobalt-800 text-sm font-medium">
                        Toutes les prestations →
                    </Link>
                </div>
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {prestations.map((p) => (
                        <article key={p.slug} className="bg-white rounded-kodem border border-brume p-6 shadow-sm hover:shadow-md transition">
                            <h3 className="text-kodem-h2 font-semibold">{p.title}</h3>
                            <p className="text-sm text-acier mt-1">{p.tagline}</p>
                            <p className="mt-4 text-sm">{p.description}</p>
                            <p className="mt-4 text-cobalt-600 font-mono text-sm">{p.price_label}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-6 py-20">
                <SectionLabel number="02">CRÉATION WEB</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold max-w-3xl">
                    Création de site internet, d'application web et de logiciel sur-mesure
                </h2>
                <p className="mt-4 text-acier max-w-2xl">
                    Kodem développe des sites internet vitrine et e-commerce, des applications web métier avec Laravel et React SSR,
                    ainsi que des logiciels et SaaS clé-en-main. Chaque projet est conçu pour être rapide, sécurisé et bien référencé dès le départ.
                </p>
                <ul className="mt-6 space-y-3 text-encre max-w-xl">
                    <li className="flex items-start gap-3">
                        <span className="font-mono text-cobalt-400 mt-0.5">→</span>
                        <span><strong>Site internet</strong> vitrine et e-commerce — design sur-mesure, SEO intégré</span>
                    </li>
                    <li className="flex items-start gap-3">
                        <span className="font-mono text-cobalt-400 mt-0.5">→</span>
                        <span><strong>Application web métier</strong> — stack Laravel + React SSR, API robuste</span>
                    </li>
                    <li className="flex items-start gap-3">
                        <span className="font-mono text-cobalt-400 mt-0.5">→</span>
                        <span><strong>Logiciel & SaaS</strong> clé-en-main — auth, paiement récurrent, hébergement inclus</span>
                    </li>
                </ul>
                <div className="mt-8">
                    <Link
                        href="/prestations"
                        onClick={() => trackClick('home_creation_cta')}
                        className="inline-flex items-center rounded-kodem border border-cobalt-600 text-cobalt-600 px-5 py-3 font-medium hover:bg-cobalt-50"
                    >
                        Découvrir nos prestations →
                    </Link>
                </div>
            </section>

            <section className="bg-white border-y border-brume">
                <div className="max-w-6xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-10">
                    <div>
                        <SectionLabel number="03">AUDIT</SectionLabel>
                        <h2 className="mt-3 text-kodem-h1 font-bold">Un audit, deux dimensions</h2>
                        <p className="text-acier mt-4">
                            Notre moteur d'audit en ligne analyse en même temps la performance SEO et la posture de sécurité
                            de votre site. Résultat en quelques secondes, rapport détaillé, note sur 100.
                        </p>
                        <ul className="mt-6 space-y-3 text-encre">
                            <li>• Balises title, meta description, Open Graph, H1</li>
                            <li>• robots.txt, sitemap.xml, temps de réponse</li>
                            <li>• HTTPS, HSTS, CSP, X-Frame-Options, Referrer-Policy</li>
                            <li>• Cookies, redirections, certificat TLS</li>
                        </ul>
                        <CodeButton href="/audit" className="mt-8">
                            lancer_mon_audit()
                        </CodeButton>
                    </div>
                    <div className="rounded-kodem bg-encre text-slate-100 p-8 font-mono text-sm">
                        <div className="text-emerald-400">$ kodem audit https://exemple.fr</div>
                        <div className="mt-3 text-slate-300">→ SEO : 92/100</div>
                        <div className="text-slate-300">→ Sécurité : 98/100</div>
                        <div className="text-slate-300">→ Score global : 95/100</div>
                        <div className="mt-4 text-acier">Certificat TLS : OK</div>
                        <div className="text-acier">HSTS : activé (2 ans)</div>
                        <div className="text-acier">CSP : stricte</div>
                        <div className="text-acier">X-Frame-Options : DENY</div>
                        <div className="text-acier">meta description : 155 car.</div>
                    </div>
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-6 py-20 text-center">
                <SectionLabel number="04" className="justify-center">ACTION</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold">Prêt à passer à l'action&nbsp;?</h2>
                <p className="text-acier mt-3 max-w-2xl mx-auto">
                    Obtenez un diagnostic SEO et sécurité en moins d'une minute, ou parlez à un expert pour
                    un projet de développement web, création de SaaS ou hébergement.
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <CodeButton
                        href="/audit"
                        onClick={() => trackClick('home_final_cta_audit')}
                    >
                        lancer_un_audit()
                    </CodeButton>
                    <CodeButton
                        href="/contact"
                        onClick={() => trackClick('home_final_cta_contact')}
                        className="bg-cobalt-900 hover:bg-cobalt-700"
                    >
                        contacter_equipe()
                    </CodeButton>
                </div>
            </section>
        </PublicLayout>
    );
}
