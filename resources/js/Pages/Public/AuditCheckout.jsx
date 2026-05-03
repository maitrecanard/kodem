import { useForm, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';

export default function AuditCheckout({ meta, audit, price, driver }) {
    const { data, setData, post, processing, errors } = useForm({
        confirm: false,
        card_number: '4242 4242 4242 4242',
        card_expiry: '12 / 34',
        card_cvc: '123',
        card_name: '',
    });

    const submit = (e) => {
        e.preventDefault();
        trackClick('audit_checkout_confirm', { audit_uuid: audit.uuid });
        post(`/audit/${audit.uuid}/pay`);
    };

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-2xl mx-auto px-6 py-12">
                <Link href={`/audit/${audit.uuid}`} className="text-indigo-600 text-sm hover:underline">← Retour au rapport</Link>

                <div className="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold">Finaliser le paiement</h1>
                            <p className="text-sm text-slate-600 mt-1">
                                Rapport d'audit complet pour <span className="font-mono break-all">{audit.url}</span>
                            </p>
                        </div>
                        <div className="text-right">
                            <div className="text-3xl font-bold">{price.label}</div>
                            <div className="text-xs text-slate-500">TTC, paiement unique</div>
                        </div>
                    </div>

                    {driver === 'stub' && (
                        <div className="mt-6 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
                            <strong>Mode démo :</strong> aucun paiement réel n'est effectué. Cochez la case ci-dessous
                            pour simuler un checkout réussi et débloquer le rapport.
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-8 space-y-5">
                        <fieldset disabled className="opacity-60 pointer-events-none">
                            <legend className="text-sm font-medium text-slate-700 mb-2">Carte bancaire (démo)</legend>
                            <div className="space-y-3">
                                <input
                                    type="text"
                                    value={data.card_number}
                                    readOnly
                                    className="w-full border border-slate-300 rounded-lg px-4 py-3 font-mono bg-slate-50"
                                />
                                <div className="grid grid-cols-2 gap-3">
                                    <input type="text" value={data.card_expiry} readOnly className="border border-slate-300 rounded-lg px-4 py-3 font-mono bg-slate-50" />
                                    <input type="text" value={data.card_cvc} readOnly className="border border-slate-300 rounded-lg px-4 py-3 font-mono bg-slate-50" />
                                </div>
                            </div>
                        </fieldset>

                        <label className="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.confirm}
                                onChange={(e) => setData('confirm', e.target.checked)}
                                className="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span className="text-sm text-slate-700">
                                Je confirme vouloir débloquer le rapport complet pour {price.label} et j'accepte les <Link href="/cgv" className="text-indigo-600 hover:underline">conditions générales de vente</Link>.
                            </span>
                        </label>
                        {errors.confirm && <div className="text-sm text-rose-600">{errors.confirm}</div>}

                        <button
                            type="submit"
                            disabled={processing || !data.confirm}
                            className="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {processing ? 'Traitement…' : `Payer ${price.label}`}
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
