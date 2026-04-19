import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function ScoreBadge({ score }) {
    if (score === null || score === undefined) return <span className="text-slate-400">—</span>;
    const color =
        score >= 90 ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
        : score >= 70 ? 'bg-amber-100 text-amber-800 border-amber-200'
        : 'bg-rose-100 text-rose-800 border-rose-200';
    return <span className={`inline-flex items-center rounded-full border px-3 py-1 text-sm font-semibold ${color}`}>{score}/100</span>;
}

function CheckRow({ check }) {
    const icon = check.status === 'pass' ? '✅' : check.status === 'warn' ? '⚠️' : '❌';
    return (
        <div className="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0">
            <div className="text-lg" aria-hidden="true">{icon}</div>
            <div className="flex-1">
                <div className="font-medium text-sm">{check.label}</div>
                <div className="text-xs text-slate-500 mt-0.5">{check.detail}</div>
            </div>
        </div>
    );
}

export default function AuditResult({ meta, audit, paidPrestations = [] }) {
    const seo = audit?.results?.seo;
    const sec = audit?.results?.security;

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-5xl mx-auto px-6 py-12">
                <Link href="/audit" className="text-indigo-600 text-sm hover:underline">← Nouvel audit</Link>
                <div className="mt-4 bg-white rounded-xl border border-slate-200 p-8 shadow-sm">
                    <h1 className="text-2xl font-bold break-all">Audit de {audit.url}</h1>
                    <p className="text-slate-500 text-sm mt-1">Référence : {audit.uuid}</p>

                    {audit.status === 'failed' ? (
                        <div className="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800">
                            <strong>Audit impossible.</strong> {audit.error}
                        </div>
                    ) : (
                        <div className="mt-8 grid md:grid-cols-3 gap-6">
                            <div className="rounded-lg border border-slate-200 p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-slate-500">Score SEO</div>
                                <div className="mt-2"><ScoreBadge score={audit.score_seo} /></div>
                            </div>
                            <div className="rounded-lg border border-slate-200 p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-slate-500">Score sécurité</div>
                                <div className="mt-2"><ScoreBadge score={audit.score_security} /></div>
                            </div>
                            <div className="rounded-lg bg-slate-900 text-white p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-slate-400">Score global</div>
                                <div className="mt-2 text-4xl font-bold">{audit.score_total ?? '—'}<span className="text-base text-slate-400">/100</span></div>
                            </div>
                        </div>
                    )}
                </div>

                {audit.status !== 'failed' && (
                    <div className="mt-8 grid md:grid-cols-2 gap-6">
                        <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                            <h2 className="font-semibold">Contrôles SEO</h2>
                            <div className="mt-3">
                                {seo?.checks?.map((c) => <CheckRow key={c.key} check={c} />)}
                            </div>
                        </div>
                        <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                            <h2 className="font-semibold">Contrôles de sécurité</h2>
                            <div className="mt-3">
                                {sec?.checks?.map((c) => <CheckRow key={c.key} check={c} />)}
                            </div>
                        </div>
                    </div>
                )}

                <section className="mt-12 bg-slate-900 text-white rounded-xl p-8">
                    <h2 className="text-xl font-semibold">Passez à l'action avec nos prestations automatisées</h2>
                    <p className="mt-2 text-slate-300 text-sm">
                        Monitoring mensuel, remédiation assistée, hébergement managé : toutes nos prestations sont tarifées et disponibles en ligne.
                    </p>
                    <div className="mt-6 grid md:grid-cols-3 gap-4">
                        {paidPrestations.filter((p) => p.price_from !== 0).slice(0, 3).map((p) => (
                            <div key={p.slug} className="rounded-lg bg-slate-800 p-5">
                                <div className="font-medium">{p.title}</div>
                                <div className="text-xs text-indigo-300 mt-1">{p.price_label}</div>
                                <div className="text-sm text-slate-300 mt-2">{p.tagline}</div>
                            </div>
                        ))}
                    </div>
                    <Link href="/prestations" className="mt-6 inline-block bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-lg font-medium">
                        Voir toutes les prestations
                    </Link>
                </section>
            </section>
        </PublicLayout>
    );
}
