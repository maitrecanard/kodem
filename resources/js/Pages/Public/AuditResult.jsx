import { useState } from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import SectionLabel from '@/Components/SectionLabel';

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
            <pre className="bg-encre text-slate-100 text-xs p-3 pr-16 rounded-kodem overflow-x-auto whitespace-pre"><code>{code}</code></pre>
            {lang && <div className="text-[10px] uppercase tracking-wide text-acier mt-1">{lang}</div>}
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
        <div className="bg-white rounded-kodem border border-brume p-5 shadow-sm">
            <div className="flex items-start gap-4">
                <div className="flex-none rounded-full bg-encre text-white w-7 h-7 flex items-center justify-center text-xs font-semibold">
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
                            <span className="text-xs text-acier">{effortLabel}</span>
                            <span className="text-xs uppercase tracking-wide text-acier">{item.category}</span>
                        </div>
                    </div>
                    <p className="text-sm text-acier mt-1">
                        <span className="font-mono text-acier">Constat : </span>{item.detail}
                    </p>
                    {item.recommendation && (
                        <>
                            <p className="text-sm text-encre mt-3">{item.recommendation.fix}</p>
                            {item.recommendation.snippet && (
                                <CodeSnippet code={item.recommendation.snippet} lang={item.recommendation.snippet_lang} />
                            )}
                            {item.recommendation.reference && (
                                <a
                                    href={item.recommendation.reference}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-2 inline-block text-xs text-cobalt-600 hover:underline"
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
    if (score === null || score === undefined) return <span className="text-acier">—</span>;
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
                <div className="text-xs text-acier mt-0.5">{check.detail}</div>
            </div>
        </div>
    );
}

function TeaserCounts({ label, counts }) {
    if (!counts || counts.total === 0) return null;
    return (
        <div className="flex items-center justify-between text-sm py-2">
            <span>{label}</span>
            <span className="font-mono text-xs text-acier">
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

export default function AuditResult({ meta, audit, paid, premium, paidPrestations = [] }) {
    const seo = audit?.results?.seo;
    const sec = audit?.results?.security;

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-5xl mx-auto px-6 py-12">
                <Link href="/audit" className="text-cobalt-600 text-sm hover:underline">← Nouvel audit</Link>
                <div className="mt-4 bg-white rounded-kodem border border-brume p-8 shadow-sm">
                    <div className="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <h1 className="text-kodem-h2 font-bold break-all">Audit de {audit.url}</h1>
                            <p className="text-acier text-sm mt-1">Référence : {audit.uuid}</p>
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
                        <div className="mt-6 rounded-kodem border border-rose-200 bg-rose-50 p-4 text-rose-800">
                            <strong>Audit impossible.</strong> {audit.error}
                        </div>
                    ) : (
                        <div className="mt-8 grid md:grid-cols-3 gap-6">
                            <div className="rounded-kodem border border-brume p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-acier">Score SEO</div>
                                <div className="mt-2">
                                    {paid
                                        ? <ScoreBadge score={audit.score_seo} />
                                        : <span className="text-acier font-mono">• •</span>
                                    }
                                </div>
                            </div>
                            <div className="rounded-kodem border border-brume p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-acier">Score sécurité</div>
                                <div className="mt-2">
                                    {paid
                                        ? <ScoreBadge score={audit.score_security} />
                                        : <span className="text-acier font-mono">• •</span>
                                    }
                                </div>
                            </div>
                            <div className="rounded-kodem bg-encre text-white p-6 text-center">
                                <div className="text-xs uppercase tracking-wide text-acier">Score global</div>
                                <div className="mt-2 text-4xl font-bold">
                                    {audit.score_total ?? '—'}
                                    <span className="text-base text-acier">/100</span>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {!paid && audit.status !== 'failed' && (
                    <div className="mt-8 bg-gradient-to-br from-cobalt-600 to-cobalt-900 text-white rounded-kodem p-8 shadow-lg">
                        <div className="flex items-start justify-between gap-6 flex-wrap">
                            <div className="max-w-xl">
                                <h2 className="text-kodem-h2 font-bold">Le rapport complet, écrit pour votre site</h2>
                                <p className="mt-2 text-cobalt-100">
                                    Ce score est automatique. Le rapport complet est{' '}
                                    <strong>rédigé manuellement</strong> : détail des contrôles SEO et
                                    sécurité, correctifs priorisés par impact, et recommandations
                                    adaptées à votre site plutôt qu'une liste générique.
                                    Livré sous {premium?.delivery_hours}, accessible par lien privé
                                    pendant 90 jours.
                                </p>
                                {audit.teaser && (
                                    <div className="mt-6 bg-white/10 rounded-kodem p-4">
                                        <TeaserCounts label="SEO" counts={audit.teaser.seo_counts} />
                                        <TeaserCounts label="Sécurité" counts={audit.teaser.security_counts} />
                                        {audit.teaser.sample_check && (
                                            <div className="mt-3 pt-3 border-t border-white/20">
                                                <div className="text-xs uppercase tracking-wide text-cobalt-200">Exemple de contrôle</div>
                                                <div className="mt-1 text-sm">
                                                    {audit.teaser.sample_check.status === 'pass' ? '✅' : audit.teaser.sample_check.status === 'warn' ? '⚠️' : '❌'}
                                                    {' '}{audit.teaser.sample_check.label}
                                                </div>
                                                <div className="text-xs text-cobalt-200 mt-0.5">{audit.teaser.sample_check.detail}</div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                            <div className="text-right">
                                <div className="text-5xl font-bold">{premium?.price_label}</div>
                                {premium?.vat_mention && (
                                    <div className="text-xs text-cobalt-200 mt-1">{premium.vat_mention}</div>
                                )}
                                <div className="text-sm text-cobalt-200 mt-1">
                                    rédigé à la main, livré sous {premium?.delivery_hours}
                                </div>
                                <Link
                                    href="/audits/premium"
                                    onClick={() => trackClick('audit_premium_cta', { audit_uuid: audit.uuid, score_total: audit.score_total })}
                                    className="mt-6 inline-flex items-center rounded-kodem bg-white text-cobalt-700 px-6 py-3 font-semibold shadow hover:bg-cobalt-50"
                                >
                                    Commander le rapport
                                </Link>
                            </div>
                        </div>
                    </div>
                )}

                {paid && audit.status !== 'failed' && (
                    <>
                        {audit.results?.action_plan?.items?.length > 0 && (
                            <section className="mt-8">
                                <div className="bg-gradient-to-br from-encre to-cobalt-900 text-white rounded-kodem p-6">
                                    <div className="flex items-start justify-between gap-4 flex-wrap">
                                        <div>
                                            <SectionLabel className="text-cobalt-200 mb-2">PLAN D'ACTION</SectionLabel>
                                            <h2 className="text-kodem-h2 font-bold">Plan d'action vers 100/100</h2>
                                            <p className="text-slate-300 text-sm mt-1">
                                                {audit.results.action_plan.items.length} action{audit.results.action_plan.items.length > 1 ? 's' : ''} triée{audit.results.action_plan.items.length > 1 ? 's' : ''} par gain potentiel et gravité.
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-xs uppercase tracking-wide text-acier">Gain potentiel global</div>
                                            <div className="text-3xl font-bold">+{audit.results.action_plan.potential_gain_total} pts</div>
                                            <div className="text-xs text-acier mt-0.5">
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
                            <section className="mt-8 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-kodem p-6 text-center">
                                <h2 className="text-kodem-h2 font-bold">🎉 Score 100/100 atteint</h2>
                                <p className="mt-2 text-sm">
                                    Tous les contrôles sont au vert. Pensez à lancer un nouvel audit régulièrement
                                    pour détecter d'éventuelles régressions — ou souscrivez au{' '}
                                    <Link href="/monitoring" className="underline font-medium">monitoring mensuel</Link>.
                                </p>
                            </section>
                        )}

                        <div className="mt-8 grid md:grid-cols-2 gap-6">
                            <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                                <h2 className="text-kodem-h2 font-semibold">Contrôles SEO</h2>
                                <div className="mt-3">
                                    {seo?.checks?.map((c) => <CheckRow key={c.key} check={c} />)}
                                </div>
                            </div>
                            <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                                <h2 className="text-kodem-h2 font-semibold">Contrôles de sécurité</h2>
                                <div className="mt-3">
                                    {sec?.checks?.map((c) => <CheckRow key={c.key} check={c} />)}
                                </div>
                            </div>
                        </div>

                        {/* Le PDF et les Core Web Vitals ne sont plus vendus : le rapport
                            complet rédigé manuellement est la seule offre payante. Ces cartes
                            ne restent affichées que pour les clients qui les ont DÉJÀ achetées,
                            afin de ne pas leur retirer un accès dû. */}
                        {(audit.pdf_paid || audit.cwv_paid) && (
                        <div className="mt-8 grid md:grid-cols-2 gap-6">
                            {audit.pdf_paid && (
                            <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="text-kodem-h2 font-semibold">Rapport PDF</h3>
                                        <p className="text-sm text-acier mt-1">Version imprimable et archivable du rapport.</p>
                                    </div>
                                </div>
                                <Link
                                    href={`/audit/${audit.uuid}/pdf`}
                                    onClick={() => trackClick('pdf_download_cta', { audit_uuid: audit.uuid })}
                                    className="mt-4 inline-flex rounded-kodem bg-cobalt-600 text-white px-4 py-2 text-sm font-medium hover:bg-cobalt-700"
                                >
                                    Télécharger le PDF
                                </Link>
                            </div>
                            )}
                            {audit.cwv_paid && (
                            <div className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="text-kodem-h2 font-semibold">Core Web Vitals</h3>
                                        <p className="text-sm text-acier mt-1">Score de performance Google (LCP, CLS, INP, FCP, TBT).</p>
                                    </div>
                                </div>
                                <Link
                                    href={`/audit/${audit.uuid}/performance`}
                                    onClick={() => trackClick('cwv_view_cta', { audit_uuid: audit.uuid })}
                                    className="mt-4 inline-flex rounded-kodem bg-cobalt-600 text-white px-4 py-2 text-sm font-medium hover:bg-cobalt-700"
                                >
                                    Voir les Core Web Vitals
                                </Link>
                            </div>
                            )}
                        </div>
                        )}
                    </>
                )}

                <section className="mt-12 bg-encre text-white rounded-kodem p-8">
                    <SectionLabel className="text-cobalt-200 mb-3">ALLER PLUS LOIN</SectionLabel>
                    <h2 className="text-kodem-h2 font-semibold">Besoin d'aller plus loin ?</h2>
                    <p className="mt-2 text-slate-300 text-sm">
                        Monitoring mensuel, remédiation assistée, hébergement managé : toutes nos prestations sont tarifées et disponibles en ligne.
                    </p>
                    <div className="mt-6 grid md:grid-cols-3 gap-4">
                        {paidPrestations.filter((p) => p.slug !== 'audit-seo' && p.slug !== 'audit-securite').slice(0, 3).map((p) => (
                            <div key={p.slug} className="rounded-kodem bg-cobalt-900 p-5">
                                <div className="font-medium">{p.title}</div>
                                <div className="text-xs text-cobalt-300 mt-1 font-mono">{p.price_label}</div>
                                <div className="text-sm text-slate-300 mt-2">{p.tagline}</div>
                            </div>
                        ))}
                    </div>
                    <Link href="/prestations" className="mt-6 inline-block bg-cobalt-600 hover:bg-cobalt-500 text-white px-5 py-2.5 rounded-kodem font-medium">
                        Voir toutes les prestations
                    </Link>
                </section>
            </section>
        </PublicLayout>
    );
}
