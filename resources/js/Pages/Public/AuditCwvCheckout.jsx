import { useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';

export default function AuditCwvCheckout({ meta, audit, price, driver }) {
    const { data, setData, post, processing, errors } = useForm({ confirm: false });
    const submit = (e) => {
        e.preventDefault();
        trackClick('cwv_checkout_confirm', { audit_uuid: audit.uuid });
        post(`/audit/${audit.uuid}/performance/pay`);
    };

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-2xl mx-auto px-6 py-12">
                <Link href={`/audit/${audit.uuid}`} className="text-indigo-600 text-sm hover:underline">← Retour au rapport</Link>
                <div className="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold">Add-on · Core Web Vitals</h1>
                            <p className="text-sm text-slate-600 mt-1">
                                Analyse de performance via Google PageSpeed Insights pour <span className="font-mono break-all">{audit.url}</span>.
                            </p>
                        </div>
                        <div className="text-right">
                            <div className="text-3xl font-bold">+{price.label}</div>
                            <div className="text-xs text-slate-500">add-on unique</div>
                        </div>
                    </div>

                    {driver === 'stub' && (
                        <div className="mt-6 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
                            <strong>Mode démo :</strong> aucun paiement réel n'est effectué.
                        </div>
                    )}

                    <ul className="mt-6 space-y-2 text-sm text-slate-700">
                        <li>• Score de performance Lighthouse (0 – 100)</li>
                        <li>• LCP (Largest Contentful Paint)</li>
                        <li>• CLS (Cumulative Layout Shift)</li>
                        <li>• INP (Interaction to Next Paint)</li>
                        <li>• FCP et TBT</li>
                        <li>• Stratégie mobile par défaut</li>
                    </ul>

                    <form onSubmit={submit} className="mt-8 space-y-5">
                        <label className="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.confirm}
                                onChange={(e) => setData('confirm', e.target.checked)}
                                className="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span className="text-sm text-slate-700">
                                Je confirme l'achat de l'add-on Core Web Vitals pour {price.label}.
                            </span>
                        </label>
                        {errors.confirm && <div className="text-sm text-rose-600">{errors.confirm}</div>}
                        <button
                            type="submit"
                            disabled={processing || !data.confirm}
                            className="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {processing ? 'Analyse en cours…' : `Payer ${price.label}`}
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
