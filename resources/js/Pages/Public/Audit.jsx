import { useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';

export default function Audit({ meta, price, paidPrestations = [] }) {
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
            <section className="bg-gradient-to-b from-indigo-600 to-indigo-800 text-white">
                <div className="max-w-3xl mx-auto px-6 py-20 text-center">
                    <p className="uppercase tracking-widest text-xs text-indigo-200">Audit automatisé</p>
                    <h1 className="mt-2 text-4xl md:text-5xl font-bold">Audit SEO et de sécurité en ligne</h1>
                    <p className="mt-4 text-indigo-100">
                        Saisissez une URL. Aperçu du score global <strong>gratuit</strong>, rapport complet et recommandations à <strong>{price?.label ?? '29,00 €'}</strong>.
                    </p>

                    <form onSubmit={submit} className="mt-10 bg-white text-slate-800 rounded-xl shadow-xl p-6 md:p-8 text-left">
                        <label className="block mb-4">
                            <span className="block text-sm font-medium mb-1">URL du site à auditer</span>
                            <input
                                type="text"
                                value={data.url}
                                onChange={(e) => setData('url', e.target.value)}
                                placeholder="https://www.exemple.fr"
                                required
                                className="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                                    className="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.email && <span className="block mt-1 text-sm text-rose-600">{errors.email}</span>}
                            </label>
                            <label className="block">
                                <span className="block text-sm font-medium mb-1">Type d'audit</span>
                                <select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                            className="mt-6 w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {processing ? 'Audit en cours…' : 'Lancer l\'audit'}
                        </button>
                        <p className="mt-3 text-xs text-slate-500 text-center">
                            Aperçu gratuit immédiat. Rapport complet à {price?.label ?? '29,00 €'} après paiement.
                            Vos données sont anonymisées. 3 audits/h maximum.
                        </p>
                    </form>
                </div>
            </section>

            <section className="max-w-5xl mx-auto px-6 py-16">
                <h2 className="text-2xl font-bold text-center">Ce que nous vérifions</h2>
                <div className="grid md:grid-cols-2 gap-8 mt-10">
                    <div>
                        <h3 className="font-semibold text-lg">SEO</h3>
                        <ul className="mt-3 space-y-2 text-slate-700">
                            <li>• Balises title, meta description, H1</li>
                            <li>• Open Graph et Twitter Cards</li>
                            <li>• Canonical, lang, viewport</li>
                            <li>• Compression HTTP</li>
                            <li>• robots.txt et sitemap.xml</li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="font-semibold text-lg">Sécurité</h3>
                        <ul className="mt-3 space-y-2 text-slate-700">
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

            <section className="bg-slate-100 border-y border-slate-200">
                <div className="max-w-5xl mx-auto px-6 py-16">
                    <h2 className="text-2xl font-bold text-center">Besoin d'aller plus loin ?</h2>
                    <p className="text-slate-600 text-center mt-2 max-w-2xl mx-auto">
                        Prolongez l'audit avec nos prestations automatiques : monitoring, remédiation, hébergement managé.
                    </p>
                    <div className="grid md:grid-cols-3 gap-6 mt-8">
                        {paidPrestations.filter((p) => p.price_from !== 0).slice(0, 3).map((p) => (
                            <article key={p.slug} className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                                <h3 className="font-semibold">{p.title}</h3>
                                <p className="text-xs text-indigo-700 mt-1 font-medium">{p.price_label}</p>
                                <p className="text-sm mt-3 text-slate-600">{p.tagline}</p>
                                <Link href="/prestations" className="mt-4 inline-block text-sm text-indigo-600 font-medium hover:underline">
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
