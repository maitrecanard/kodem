import { useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';

export default function AuditPdfCheckout({ meta, audit, price, driver }) {
    const { data, setData, post, processing, errors } = useForm({ confirm: false });
    const submit = (e) => {
        e.preventDefault();
        trackClick('pdf_checkout_confirm', { audit_uuid: audit.uuid });
        post(`/audit/${audit.uuid}/pdf/pay`);
    };

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-2xl mx-auto px-6 py-12">
                <Link href={`/audit/${audit.uuid}`} className="text-indigo-600 text-sm hover:underline">← Retour au rapport</Link>
                <div className="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold">Add-on · Rapport PDF</h1>
                            <p className="text-sm text-slate-600 mt-1">
                                Version PDF téléchargeable et imprimable du rapport d'audit pour <span className="font-mono break-all">{audit.url}</span>.
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
                        <li>• PDF A4 prêt à imprimer</li>
                        <li>• Inclut les 20 contrôles détaillés</li>
                        <li>• Inclut les Core Web Vitals si vous avez cet add-on</li>
                        <li>• Téléchargement immédiat après paiement</li>
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
                                Je confirme l'achat de l'add-on PDF pour {price.label}.
                            </span>
                        </label>
                        {errors.confirm && <div className="text-sm text-rose-600">{errors.confirm}</div>}
                        <button
                            type="submit"
                            disabled={processing || !data.confirm}
                            className="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {processing ? 'Traitement…' : `Payer ${price.label} et télécharger`}
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
