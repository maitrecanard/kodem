import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function Metric({ label, value, hint }) {
    return (
        <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm text-center">
            <div className="text-xs uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-2 text-2xl font-bold">{value ?? '—'}</div>
            {hint && <div className="mt-1 text-xs text-slate-500">{hint}</div>}
        </div>
    );
}

export default function AuditCwv({ meta, audit }) {
    const cwv = audit?.cwv_results ?? {};
    const score = cwv.performance_score;
    const scoreColor =
        score === null || score === undefined ? 'text-slate-400'
        : score >= 90 ? 'text-emerald-600'
        : score >= 50 ? 'text-amber-600'
        : 'text-rose-600';

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-5xl mx-auto px-6 py-12">
                <Link href={`/audit/${audit.uuid}`} className="text-indigo-600 text-sm hover:underline">← Retour au rapport</Link>

                <div className="mt-4 bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                    <h1 className="text-2xl font-bold">Core Web Vitals</h1>
                    <p className="text-sm text-slate-500 mt-1 break-all">{audit.url}</p>

                    {cwv.status === 'failed' ? (
                        <div className="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800">
                            <strong>Analyse de performance indisponible.</strong> {cwv.error}
                        </div>
                    ) : (
                        <>
                            <div className="mt-8 flex items-center justify-center">
                                <div className={`text-7xl font-bold ${scoreColor}`}>
                                    {score ?? '—'}
                                    <span className="text-2xl text-slate-400 ml-1">/100</span>
                                </div>
                            </div>
                            <p className="text-center text-slate-500 text-sm mt-1">
                                Score Lighthouse Performance (stratégie {cwv.strategy ?? 'mobile'})
                            </p>

                            <div className="mt-10 grid gap-4 md:grid-cols-5">
                                <Metric label="LCP" value={cwv.lcp} hint="Largest Contentful Paint" />
                                <Metric label="CLS" value={cwv.cls} hint="Cumulative Layout Shift" />
                                <Metric label="INP" value={cwv.inp} hint="Interaction to Next Paint" />
                                <Metric label="FCP" value={cwv.fcp} hint="First Contentful Paint" />
                                <Metric label="TBT" value={cwv.tbt} hint="Total Blocking Time" />
                            </div>

                            <p className="mt-10 text-xs text-slate-500 text-center">
                                Données fournies par Google PageSpeed Insights · récupérées le{' '}
                                {cwv.fetched_at ? new Date(cwv.fetched_at).toLocaleString('fr-FR') : '—'}.
                            </p>
                        </>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}
