import AdminLayout from '@/Layouts/AdminLayout';

function Stat({ label, value }) {
    return (
        <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-1 text-2xl font-bold">{value}</div>
        </div>
    );
}

function FunnelRow({ step, isFirst }) {
    const pct = step.rate ?? 0;
    const barW = Math.max(2, pct);
    return (
        <div className="py-3 border-b border-slate-100 last:border-0">
            <div className="flex items-center justify-between gap-3">
                <div className="font-medium text-sm">{step.label}</div>
                <div className="font-mono text-sm text-slate-600">
                    {step.count.toLocaleString('fr-FR')}
                    <span className="ml-2 text-xs text-slate-500">
                        {isFirst ? 'base' : `${pct}%`}
                    </span>
                </div>
            </div>
            <div className="mt-1 h-2 bg-slate-100 rounded overflow-hidden">
                <div className="h-full bg-indigo-500" style={{ width: barW + '%' }} />
            </div>
        </div>
    );
}

function TypeBadge({ type }) {
    const colors = {
        'button_click': 'bg-sky-50 text-sky-800 border-sky-200',
        'audit.submitted': 'bg-slate-50 text-slate-800 border-slate-200',
        'audit.completed': 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'audit.failed': 'bg-rose-50 text-rose-800 border-rose-200',
        'audit.paid': 'bg-indigo-50 text-indigo-800 border-indigo-200',
        'audit.pdf.paid': 'bg-indigo-50 text-indigo-800 border-indigo-200',
        'audit.cwv.paid': 'bg-indigo-50 text-indigo-800 border-indigo-200',
        'pdf.downloaded': 'bg-violet-50 text-violet-800 border-violet-200',
        'monitoring.subscribed': 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'monitoring.cancelled': 'bg-amber-50 text-amber-800 border-amber-200',
        'contact.submitted': 'bg-teal-50 text-teal-800 border-teal-200',
        'contact.spam_blocked': 'bg-rose-50 text-rose-800 border-rose-200',
        'view': 'bg-slate-50 text-slate-600 border-slate-200',
    };
    const cls = colors[type] ?? 'bg-slate-50 text-slate-600 border-slate-200';
    return (
        <span className={`inline-block text-xs rounded-full border px-2 py-0.5 ${cls}`}>{type}</span>
    );
}

export default function Events({ stats, funnel = [], topEvents = [], countsByType = [], recent = [] }) {
    return (
        <AdminLayout title="Événements & conversion">
            <div className="grid gap-4 md:grid-cols-4">
                <Stat label="Événements 7j" value={stats.events_7d} />
                <Stat label="Événements 30j" value={stats.events_30d} />
                <Stat label="Visites 30j" value={stats.visits_30d} />
                <Stat label="Sessions uniques 30j" value={stats.unique_sessions_30d} />
            </div>

            <section className="mt-8 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <h2 className="font-semibold">Funnel de conversion (30 j)</h2>
                <p className="text-xs text-slate-500 mt-1">Chaque étape en pourcentage de la base (= nombre de visites).</p>
                <div className="mt-4">
                    {funnel.map((s, i) => (
                        <FunnelRow key={s.step} step={s} isFirst={i === 0} />
                    ))}
                </div>
            </section>

            <div className="mt-8 grid gap-6 md:grid-cols-2">
                <div className="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                    <h2 className="font-semibold">Top événements (30 j)</h2>
                    <table className="w-full mt-3 text-sm">
                        <thead className="text-left text-slate-500">
                            <tr><th className="py-2">Type / nom</th><th className="text-right">Total</th></tr>
                        </thead>
                        <tbody>
                            {topEvents.map((t) => (
                                <tr key={`${t.type}-${t.name}`} className="border-t border-slate-100">
                                    <td className="py-2">
                                        <TypeBadge type={t.type} />
                                        <span className="ml-2 font-mono text-xs text-slate-600">{t.name}</span>
                                    </td>
                                    <td className="text-right font-mono">{t.total}</td>
                                </tr>
                            ))}
                            {topEvents.length === 0 && (
                                <tr><td colSpan={2} className="py-4 text-slate-400 text-center">Aucun événement.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                    <h2 className="font-semibold">Volumes par type (30 j)</h2>
                    <ul className="mt-3 divide-y divide-slate-100 text-sm">
                        {countsByType.map((c) => (
                            <li key={c.type} className="py-2 flex items-center justify-between">
                                <TypeBadge type={c.type} />
                                <span className="font-mono">{c.total}</span>
                            </li>
                        ))}
                        {countsByType.length === 0 && <li className="py-2 text-slate-400 text-center">—</li>}
                    </ul>
                </div>
            </div>

            <section className="mt-8 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <h2 className="font-semibold">Flux récent</h2>
                <div className="mt-3 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="text-left text-slate-500">
                            <tr>
                                <th className="py-2 pr-2">Quand</th>
                                <th className="py-2 pr-2">Type</th>
                                <th className="py-2 pr-2">Nom</th>
                                <th className="py-2 pr-2">URL</th>
                                <th className="py-2 pr-2">Session</th>
                                <th className="py-2 pr-2">Métadonnées</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recent.map((e) => (
                                <tr key={e.id} className="border-t border-slate-100 align-top">
                                    <td className="py-2 pr-2 font-mono text-xs text-slate-600 whitespace-nowrap">
                                        {new Date(e.created_at).toLocaleString('fr-FR')}
                                    </td>
                                    <td className="py-2 pr-2"><TypeBadge type={e.type} /></td>
                                    <td className="py-2 pr-2 font-mono text-xs">{e.name}</td>
                                    <td className="py-2 pr-2 font-mono text-xs truncate max-w-[180px]">/{e.url ?? '—'}</td>
                                    <td className="py-2 pr-2 font-mono text-[10px] text-slate-500">
                                        {e.session_hash ? e.session_hash.slice(0, 10) + '…' : '—'}
                                    </td>
                                    <td className="py-2 pr-2 text-xs">
                                        {e.metadata
                                            ? <code className="text-[10px]">{JSON.stringify(e.metadata)}</code>
                                            : '—'}
                                    </td>
                                </tr>
                            ))}
                            {recent.length === 0 && (
                                <tr><td colSpan={6} className="py-6 text-slate-400 text-center">Aucun événement.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>
        </AdminLayout>
    );
}
