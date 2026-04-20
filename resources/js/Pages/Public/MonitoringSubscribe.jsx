import { useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function MonitoringSubscribe({ meta, price, period_days, driver }) {
    const { data, setData, post, processing, errors } = useForm({
        url: '', email: '', confirm: false,
    });
    const submit = (e) => { e.preventDefault(); post('/monitoring/subscribe'); };

    return (
        <PublicLayout meta={meta}>
            <section className="bg-gradient-to-b from-indigo-600 to-indigo-800 text-white">
                <div className="max-w-3xl mx-auto px-6 py-20">
                    <p className="uppercase tracking-widest text-xs text-indigo-200">Abonnement</p>
                    <h1 className="mt-2 text-4xl md:text-5xl font-bold">Monitoring mensuel — {price.label}/mois</h1>
                    <p className="mt-4 text-indigo-100 text-lg">
                        Audit SEO + sécurité automatique chaque semaine. Rapport email et alerte en cas de régression.
                    </p>

                    <ul className="mt-8 space-y-2 text-indigo-100">
                        <li>✓ Audit hebdomadaire automatique ({period_days} jours par période)</li>
                        <li>✓ Email de rapport à chaque contrôle</li>
                        <li>✓ Alerte immédiate si le score chute</li>
                        <li>✓ Historique des scores dans votre dashboard</li>
                        <li>✓ Rapport complet inclus (pas d'upsell)</li>
                        <li>✓ Sans engagement — résiliable à tout moment</li>
                    </ul>
                </div>
            </section>

            <section className="max-w-2xl mx-auto px-6 py-12">
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <h2 className="text-xl font-semibold">Activer l'abonnement</h2>
                    {driver === 'stub' && (
                        <div className="mt-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
                            <strong>Mode démo :</strong> aucun paiement réel n'est effectué — l'abonnement est activé immédiatement pour {period_days} jours.
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6 space-y-4">
                        <label className="block">
                            <span className="block text-sm font-medium text-slate-700 mb-1">URL à surveiller</span>
                            <input
                                type="text"
                                value={data.url}
                                onChange={(e) => setData('url', e.target.value)}
                                placeholder="https://monsite.fr"
                                required
                                className="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            {errors.url && <span className="block mt-1 text-sm text-rose-600">{errors.url}</span>}
                        </label>
                        <label className="block">
                            <span className="block text-sm font-medium text-slate-700 mb-1">Email de réception des rapports</span>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                className="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            {errors.email && <span className="block mt-1 text-sm text-rose-600">{errors.email}</span>}
                        </label>
                        <label className="flex items-start gap-3 cursor-pointer pt-2">
                            <input
                                type="checkbox"
                                checked={data.confirm}
                                onChange={(e) => setData('confirm', e.target.checked)}
                                className="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span className="text-sm text-slate-700">
                                Je confirme l'abonnement à {price.label}/mois et j'accepte les <Link href="/cgv" className="text-indigo-600 hover:underline">CGV</Link>.
                            </span>
                        </label>
                        {errors.confirm && <div className="text-sm text-rose-600">{errors.confirm}</div>}
                        <button
                            type="submit"
                            disabled={processing || !data.confirm}
                            className="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {processing ? 'Activation…' : `Activer l'abonnement (${price.label}/mois)`}
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
