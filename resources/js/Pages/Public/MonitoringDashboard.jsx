import { Link, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function MonitoringDashboard({ meta, subscription }) {
    const cancel = () => {
        if (confirm('Annuler l\'abonnement ? Il reste actif jusqu\'à la fin de la période en cours.')) {
            router.post(`/monitoring/${subscription.token}/cancel`);
        }
    };

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-3xl mx-auto px-6 py-12">
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <div className="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <h1 className="text-2xl font-bold">Abonnement monitoring</h1>
                            <p className="text-sm text-slate-500 mt-1 break-all">{subscription.url}</p>
                        </div>
                        <span className={
                            'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium ' +
                            (subscription.active
                                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                                : 'bg-slate-50 border-slate-200 text-slate-600')
                        }>
                            {subscription.active ? '✓ Actif' : subscription.status}
                        </span>
                    </div>

                    <dl className="mt-6 grid gap-4 md:grid-cols-2 text-sm">
                        <div><dt className="text-slate-500">Email de contact</dt><dd className="mt-1">{subscription.email}</dd></div>
                        <div><dt className="text-slate-500">Actif jusqu'au</dt><dd className="mt-1">{subscription.active_until ? new Date(subscription.active_until).toLocaleDateString('fr-FR') : '—'}</dd></div>
                        <div><dt className="text-slate-500">Dernier audit</dt><dd className="mt-1">{subscription.last_run_at ? new Date(subscription.last_run_at).toLocaleString('fr-FR') : '— (en attente)'}</dd></div>
                        <div><dt className="text-slate-500">Dernier score</dt><dd className="mt-1 font-semibold">{subscription.last_score_total ?? '—'}/100</dd></div>
                    </dl>

                    <div className="mt-8 flex items-center gap-3 flex-wrap">
                        {subscription.last_audit_uuid && (
                            <Link href={`/audit/${subscription.last_audit_uuid}`}
                                  className="rounded-md bg-indigo-600 text-white px-4 py-2 font-medium hover:bg-indigo-700">
                                Voir le dernier rapport
                            </Link>
                        )}
                        {subscription.status === 'active' && (
                            <button onClick={cancel}
                                    className="rounded-md border border-rose-300 text-rose-700 px-4 py-2 font-medium hover:bg-rose-50">
                                Annuler l'abonnement
                            </button>
                        )}
                    </div>

                    <p className="mt-8 text-xs text-slate-500">
                        Conservez ce lien précieusement : il vous permet de revenir sur votre tableau de bord sans créer de compte.
                    </p>
                </div>
            </section>
        </PublicLayout>
    );
}
