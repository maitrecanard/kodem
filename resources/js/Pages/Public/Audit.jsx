import { useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';

export default function Audit({ meta, premium, paidPrestations = [], flash }) {
    const { data, setData, post, processing, errors } = useForm({
        url: '',
        email: '',
        type: 'full',
    });

    const submit = (e) => {
        e.preventDefault();
        trackClick('audit_submit_button', { type: data.type, has_email: !!data.email });
        post('/audit');
    };

    return (
        <PublicLayout meta={meta}>
            <Banner
                image="/images/banniere-kodem.webp"
                imageSources={[
                    { src: '/images/banniere-kodem.webp', width: 1006 },
                    { src: '/images/banniere-kodem-2x.webp', width: 2012 },
                ]}
                title="Audit SEO & sécurité en ligne"
                cta={
                    <a
                        href="#audit-form"
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Lancer mon audit
                    </a>
                }
            />

            <section id="audit-form" className="max-w-3xl mx-auto px-6 py-16">
                <SectionLabel className="mb-4">AUDIT AUTOMATISÉ</SectionLabel>
                <p className="animate-kodem-fade text-acier mb-6">
                    Saisissez une URL et obtenez gratuitement le score global de votre site,
                    en quelques secondes. Pour une analyse détaillée avec des recommandations
                    rédigées pour votre cas, commandez le{' '}
                    <Link href="/audits/premium" className="text-cobalt-600 underline">
                        rapport complet
                    </Link>
                    .
                </p>
                {flash?.error && (
                    <div
                        role="alert"
                        className="mb-6 rounded-kodem border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3"
                    >
                        {flash.error}
                    </div>
                )}
                <form onSubmit={submit} className="bg-white text-encre rounded-kodem shadow-xl border border-brume p-6 md:p-8">
                    <label className="block mb-4">
                        <span className="block text-sm font-medium mb-1">URL du site à auditer</span>
                        <input
                            type="text"
                            value={data.url}
                            onChange={(e) => setData('url', e.target.value)}
                            placeholder="https://www.exemple.fr"
                            required
                            className="w-full border border-cobalt-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-cobalt-500"
                        />
                        {errors.url && <span className="block mt-1 text-sm text-rose-600">{errors.url}</span>}
                    </label>

                    <div className="grid md:grid-cols-2 gap-4">
                        <label className="block">
                            <span className="block text-sm font-medium mb-1">Email (facultatif)</span>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="vous@exemple.fr"
                                className="w-full border border-cobalt-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-cobalt-500"
                            />
                            {errors.email && <span className="block mt-1 text-sm text-rose-600">{errors.email}</span>}
                        </label>
                        <label className="block">
                            <span className="block text-sm font-medium mb-1">Type d'audit</span>
                            <select
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value)}
                                className="w-full border border-cobalt-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-cobalt-500"
                            >
                                <option value="full">SEO + Sécurité</option>
                                <option value="seo">SEO uniquement</option>
                                <option value="security">Sécurité uniquement</option>
                            </select>
                        </label>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-6 w-full bg-cobalt-600 text-white py-3 rounded-kodem font-semibold hover:bg-cobalt-700 disabled:opacity-60"
                    >
                        {processing ? 'Audit en cours…' : 'Lancer l\'audit'}
                    </button>
                    <p className="mt-3 text-xs text-acier text-center">
                        Gratuit et immédiat. Vos données sont anonymisées. 3 audits/h maximum.
                    </p>
                </form>
            </section>

            <section className="kodem-reveal max-w-5xl mx-auto px-6 py-16">
                <SectionLabel number="01" className="justify-center mb-4">CE QUE NOUS VÉRIFIONS</SectionLabel>
                <h2 className="text-kodem-h1 font-bold text-center">Ce que nous vérifions</h2>
                <div className="grid md:grid-cols-2 gap-8 mt-10">
                    <div>
                        <h3 className="text-kodem-h2 font-semibold">SEO</h3>
                        <ul className="mt-3 space-y-2 text-encre">
                            <li>• Balises title, meta description, H1</li>
                            <li>• Open Graph et Twitter Cards</li>
                            <li>• Canonical, lang, viewport</li>
                            <li>• Compression HTTP</li>
                            <li>• robots.txt et sitemap.xml</li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-kodem-h2 font-semibold">Sécurité</h3>
                        <ul className="mt-3 space-y-2 text-encre">
                            <li>• HTTPS, HSTS, certificat TLS</li>
                            <li>• Content-Security-Policy</li>
                            <li>• X-Frame-Options, X-Content-Type-Options</li>
                            <li>• Referrer-Policy, Permissions-Policy</li>
                            <li>• Cookies Secure / HttpOnly</li>
                            <li>• Masquage Server / X-Powered-By</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section className="kodem-reveal bg-brume border-y border-brume">
                <div className="max-w-5xl mx-auto px-6 py-16">
                    <SectionLabel number="02" className="justify-center mb-4">ALLER PLUS LOIN</SectionLabel>
                    <h2 className="text-kodem-h1 font-bold text-center">Besoin d'aller plus loin ?</h2>
                    <p className="text-acier text-center mt-2 max-w-2xl mx-auto">
                        Prolongez l'audit avec nos prestations automatiques : monitoring, remédiation, hébergement managé.
                    </p>
                    <div className="grid md:grid-cols-3 gap-6 mt-8">
                        {paidPrestations.filter((p) => p.price_from !== 0).slice(0, 3).map((p) => (
                            <article key={p.slug} className="kodem-card bg-white rounded-kodem border border-brume p-6 shadow-sm">
                                <h3 className="text-kodem-h2 font-semibold">{p.title}</h3>
                                <p className="text-xs text-cobalt-600 mt-1 font-mono">{p.price_label}</p>
                                <p className="text-sm mt-3 text-acier">{p.tagline}</p>
                                <Link href="/prestations" className="mt-4 inline-block text-sm text-cobalt-600 font-medium hover:underline">
                                    En savoir plus →
                                </Link>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
