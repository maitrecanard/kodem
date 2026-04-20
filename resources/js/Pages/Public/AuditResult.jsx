import { useState } from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function CodeSnippet({ code, lang }) {
    const [copied, setCopied] = useState(false);
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(code);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch { /* noop */ }
    };
    return (
        <div className="relative mt-2">
            <button
                type="button"
                onClick={copy}
                className="absolute top-1 right-1 text-[10px] uppercase tracking-wide bg-slate-700 text-slate-100 px-2 py-0.5 rounded hover:bg-slate-600"
            >
                {copied ? 'copié' : 'copier'}
            </button>
            <pre className="bg-slate-900 text-slate-100 text-xs p-3 pr-16 rounded-md overflow-x-auto whitespace-pre"><code>{code}</code></pre>
            {lang && <div className="text-[10px] uppercase tracking-wide text-slate-500 mt-1">{lang}</div>}
        </div>
    );
}

function ActionPlanItem({ item, index }) {
    const statusColor = item.status === 'fail'
        ? 'bg-rose-50 border-rose-200 text-rose-800'
        : 'bg-amber-50 border-amber-200 text-amber-800';
    const effortLabel = {
        low: 'Effort faible',
        medium: 'Effort modéré',
        high: 'Effort élevé',
    }[item.recommendation?.effort] ?? 'Effort modéré';

    return (
        <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div className="flex items-start gap-4">
                <div className="flex-none rounded-full bg-slate-900 text-white w-7 h-7 flex items-center justify-center text-xs font-semibold">
                    {index + 1}
                </div>
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-3 flex-wrap">
                        <h3 className="font-semibold">{item.label}</h3>
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className={`text-xs rounded-full border px-2 py-0.5 ${statusColor}`}>
                                {item.status === 'fail' ? 'À corriger' : 'À améliorer'}
                            </span>
                            <span className="text-xs rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 px-2 py-0.5">
                                +{item.potential_gain} pts
                            </span>
                            <span className="text-xs text-slate-500">{effortLabel}</span>
                            <span className="text-xs uppercase tracking-wide text-slate-500">{item.category}</span>
                        </div>
                    </div>
                    <p className="text-sm text-slate-600 mt-1">
                        <span className="font-mono text-slate-500">Constat : </span>{item.detail}
                    </p>
                    {item.recommendation && (
                        <>
                            <p className="text-sm text-slate-800 mt-3">{item.recommendation.fix}</p>
                            {item.recommendation.snippet && (
                                <CodeSnippet code={item.recommendation.snippet} lang={item.recommendation.snippet_lang} />
                            )}
                            {item.recommendation.reference && (
                                <a
                                    href={item.recommendation.reference}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-2 inline-block text-xs text-indigo-600 hover:underline"
                                >
                                    Documentation de référence →
                                </a>
                            )}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}

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
                                    Détail des 20 contrôles SEO + sécurité, <strong>plan d'action priorisé pour atteindre 100/100</strong>
                                    (snippets nginx/HTML à copier-coller, gain de points estimé par correction)
                                    et lien partageable pendant 90 jours.
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
                    <>
                        {audit.results?.action_plan?.items?.length > 0 && (
                            <section className="mt-8">
                                <div className="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-xl p-6">
                                    <div className="flex items-start justify-between gap-4 flex-wrap">
                                        <div>
                                            <h2 className="text-xl font-bold">Plan d'action vers 100/100</h2>
                                            <p className="text-slate-300 text-sm mt-1">
                                                {audit.results.action_plan.items.length} action{audit.results.action_plan.items.length > 1 ? 's' : ''} triée{audit.results.action_plan.items.length > 1 ? 's' : ''} par gain potentiel et gravité.
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-xs uppercase tracking-wide text-slate-400">Gain potentiel global</div>
                                            <div className="text-3xl font-bold">+{audit.results.action_plan.potential_gain_total} pts</div>
                                            <div className="text-xs text-slate-400 mt-0.5">
                                                SEO +{audit.results.action_plan.potential_gain_seo} · Sécurité +{audit.results.action_plan.potential_gain_security}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 space-y-3">
                                    {audit.results.action_plan.items.map((it, i) => (
                                        <ActionPlanItem key={`${it.category}-${it.key}`} item={it} index={i} />
                                    ))}
                                </div>
                            </section>
                        )}

                        {audit.results?.action_plan?.items?.length === 0 && (
                            <section className="mt-8 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl p-6 text-center">
                                <h2 className="text-xl font-bold">🎉 Score 100/100 atteint</h2>
                                <p className="mt-2 text-sm">
                                    Tous les contrôles sont au vert. Pensez à lancer un nouvel audit régulièrement
                                    pour détecter d'éventuelles régressions — ou souscrivez au{' '}
                                    <Link href="/monitoring" className="underline font-medium">monitoring mensuel</Link>.
                                </p>
                            </section>
                        )}

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

                        <div className="mt-8 grid md:grid-cols-2 gap-6">
                            <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="font-semibold">Rapport PDF</h3>
                                        <p className="text-sm text-slate-600 mt-1">Version imprimable et archivable du rapport.</p>
                                    </div>
                                    <span className="text-lg font-bold whitespace-nowrap">+{audit.pdf_price_label}</span>
                                </div>
                                <Link
                                    href={audit.pdf_paid ? `/audit/${audit.uuid}/pdf` : `/audit/${audit.uuid}/pdf/pay`}
                                    className="mt-4 inline-flex rounded-md bg-indigo-600 text-white px-4 py-2 text-sm font-medium hover:bg-indigo-700"
                                >
                                    {audit.pdf_paid ? 'Télécharger le PDF' : 'Débloquer le PDF'}
                                </Link>
                            </div>
                            <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="font-semibold">Core Web Vitals</h3>
                                        <p className="text-sm text-slate-600 mt-1">Score de performance Google (LCP, CLS, INP, FCP, TBT).</p>
                                    </div>
                                    <span className="text-lg font-bold whitespace-nowrap">+{audit.cwv_price_label}</span>
                                </div>
                                <Link
                                    href={audit.cwv_paid ? `/audit/${audit.uuid}/performance` : `/audit/${audit.uuid}/performance/pay`}
                                    className="mt-4 inline-flex rounded-md bg-indigo-600 text-white px-4 py-2 text-sm font-medium hover:bg-indigo-700"
                                >
                                    {audit.cwv_paid ? 'Voir les Core Web Vitals' : 'Débloquer les Core Web Vitals'}
                                </Link>
                            </div>
                        </div>
                    </>
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
