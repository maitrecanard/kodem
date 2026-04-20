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

function TeaserCounts({ label, counts }) {
    if (!counts || counts.total === 0) return null;
    return (
        <div className="flex items-center justify-between text-sm py-2">
            <span>{label}</span>
            <span className="font-mono text-xs text-slate-500">
                <span className="text-emerald-600">{counts.pass} OK</span>
                {' · '}
                <span className="text-amber-600">{counts.warn} alertes</span>
                {' · '}
                <span className="text-rose-600">{counts.fail} KO</span>
                {' · '}
                <span>sur {counts.total}</span>
            </span>
        </div>
    );
}

export default function AuditResult({ meta, audit, paid, price, paidPrestations = [] }) {
    const seo = audit?.results?.seo;
    const sec = audit?.results?.security;

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-5xl mx-auto px-6 py-12">
                <Link href="/audit" className="text-indigo-600 text-sm hover:underline">← Nouvel audit</Link>
                <div className="mt-4 bg-white rounded-xl border border-slate-200 p-8 shadow-sm">
                    <div className="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <h1 className="text-2xl font-bold break-all">Audit de {audit.url}</h1>
                            <p className="text-slate-500 text-sm mt-1">Référence : {audit.uuid}</p>
                        </div>
                        {paid ? (
                            <span className="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-1 text-xs font-medium">
                                ✓ Rapport complet débloqué
                            </span>
                        ) : (
                            <span className="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 text-amber-800 px-3 py-1 text-xs font-medium">
                                Aperçu gratuit
                            </span>
                        )}
                    </div>

                    {audit.status === 'failed' ? (
                        <div className="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800">
                            <strong>Audit impossible.</strong> {audit.error}
                        </div>
                    ) : (
                        <div className="mt-8 grid md:grid-cols-3 gap-6">
                            <div className="rounded-lg border border-slate-200 p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-slate-500">Score SEO</div>
                                <div className="mt-2">
                                    {paid
                                        ? <ScoreBadge score={audit.score_seo} />
                                        : <span className="text-slate-400 font-mono">• •</span>
                                    }
                                </div>
                            </div>
                            <div className="rounded-lg border border-slate-200 p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-slate-500">Score sécurité</div>
                                <div className="mt-2">
                                    {paid
                                        ? <ScoreBadge score={audit.score_security} />
                                        : <span className="text-slate-400 font-mono">• •</span>
                                    }
                                </div>
                            </div>
                            <div className="rounded-lg bg-slate-900 text-white p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-slate-400">Score global</div>
                                <div className="mt-2 text-4xl font-bold">
                                    {audit.score_total ?? '—'}
                                    <span className="text-base text-slate-400">/100</span>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {!paid && audit.status !== 'failed' && (
                    <div className="mt-8 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white rounded-xl p-8 shadow-lg">
                        <div className="flex items-start justify-between gap-6 flex-wrap">
                            <div className="max-w-xl">
                                <h2 className="text-2xl font-bold">Débloquez le rapport complet</h2>
                                <p className="mt-2 text-indigo-100">
                                    Obtenez le détail des 20 contrôles SEO + sécurité, les recommandations priorisées
                                    et un lien partageable pendant 90 jours.
                                </p>
                                {audit.teaser && (
                                    <div className="mt-6 bg-white/10 rounded-lg p-4">
                                        <TeaserCounts label="SEO" counts={audit.teaser.seo_counts} />
                                        <TeaserCounts label="Sécurité" counts={audit.teaser.security_counts} />
                                        {audit.teaser.sample_check && (
                                            <div className="mt-3 pt-3 border-t border-white/20">
                                                <div className="text-xs uppercase tracking-wide text-indigo-200">Exemple de contrôle</div>
                                                <div className="mt-1 text-sm">
                                                    {audit.teaser.sample_check.status === 'pass' ? '✅' : audit.teaser.sample_check.status === 'warn' ? '⚠️' : '❌'}
                                                    {' '}{audit.teaser.sample_check.label}
                                                </div>
                                                <div className="text-xs text-indigo-200 mt-0.5">{audit.teaser.sample_check.detail}</div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                            <div className="text-right">
                                <div className="text-5xl font-bold">{price?.label}</div>
                                <div className="text-sm text-indigo-200 mt-1">paiement unique, TTC</div>
                                <Link
                                    href={`/audit/${audit.uuid}/pay`}
                                    className="mt-6 inline-flex items-center rounded-md bg-white text-indigo-700 px-6 py-3 font-semibold shadow hover:bg-indigo-50"
                                >
                                    Débloquer maintenant
                                </Link>
                            </div>
                        </div>
                    </div>
                )}

                {paid && audit.status !== 'failed' && (
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
                    <h2 className="text-xl font-semibold">Besoin d'aller plus loin ?</h2>
                    <p className="mt-2 text-slate-300 text-sm">
                        Monitoring mensuel, remédiation assistée, hébergement managé : toutes nos prestations sont tarifées et disponibles en ligne.
                    </p>
                    <div className="mt-6 grid md:grid-cols-3 gap-4">
                        {paidPrestations.filter((p) => p.slug !== 'audit-seo' && p.slug !== 'audit-securite').slice(0, 3).map((p) => (
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
