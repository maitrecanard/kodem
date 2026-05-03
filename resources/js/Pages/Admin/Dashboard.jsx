import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

function Stat({ label, value }) {
    return (
        <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-1 text-2xl font-bold">{value}</div>
        </div>
    );
}

export default function Dashboard({ stats, topPages = [], recentAudits = [], recentMessages = [] }) {
    return (
        <AdminLayout title="Tableau de bord">
            <div className="grid gap-4 md:grid-cols-4">
                <Stat label="Visites 7j" value={stats.visits_7d} />
                <Stat label="Visites 30j" value={stats.visits_30d} />
                <Stat label="Visiteurs uniques 30j" value={stats.unique_visitors_30d} />
                <Stat label="Audits total" value={stats.audits_total} />
                <Stat label="Audits 7j" value={stats.audits_7d} />
                <Stat label="Messages total" value={stats.messages_total} />
                <Stat label="Messages non lus" value={stats.messages_new} />
            </div>

            <div className="grid gap-6 md:grid-cols-2 mt-8">
                <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                    <h2 className="font-semibold">Pages les plus vues (30j)</h2>
                    <table className="w-full mt-3 text-sm">
                        <thead className="text-left text-slate-500">
                            <tr><th className="py-2">URL</th><th>Visites</th></tr>
                        </thead>
                        <tbody>
                            {topPages.map((p) => (
                                <tr key={p.url} className="border-t border-slate-100">
                                    <td className="py-2 font-mono truncate">/{p.url}</td>
                                    <td>{p.total}</td>
                                </tr>
                            ))}
                            {topPages.length === 0 && (<tr><td colSpan={2} className="py-4 text-slate-400">Pas encore de trafic.</td></tr>)}
                        </tbody>
                    </table>
                </div>
                <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                    <h2 className="font-semibold">Audits récents</h2>
                    <ul className="mt-3 divide-y divide-slate-100 text-sm">
                        {recentAudits.map((a) => (
                            <li key={a.uuid} className="py-2 flex justify-between gap-4">
                                <Link href={`/admin/audits/${a.uuid}`} className="truncate text-indigo-700 hover:underline">{a.url}</Link>
                                <span className="text-slate-500">{a.score_total ?? '—'}/100</span>
                            </li>
                        ))}
                        {recentAudits.length === 0 && <li className="py-2 text-slate-400">Aucun audit.</li>}
                    </ul>
                </div>
                <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm md:col-span-2">
                    <h2 className="font-semibold">Messages récents</h2>
                    <ul className="mt-3 divide-y divide-slate-100 text-sm">
                        {recentMessages.map((m) => (
                            <li key={m.id} className="py-2 flex justify-between gap-4">
                                <Link href={`/admin/messages/${m.id}`} className="truncate text-indigo-700 hover:underline">
                                    {m.subject} — {m.name} &lt;{m.email}&gt;
                                </Link>
                                <span className="text-xs rounded-full bg-slate-100 px-2 py-0.5">{m.status}</span>
                            </li>
                        ))}
                        {recentMessages.length === 0 && <li className="py-2 text-slate-400">Aucun message.</li>}
                    </ul>
                </div>
            </div>
        </AdminLayout>
    );
}
