import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const PERIODS = [
    { key: 'day', label: 'Par jour' },
    { key: 'month', label: 'Par mois' },
    { key: 'year', label: 'Par année' },
];

function Stat({ label, value }) {
    return (
        <div className="bg-white rounded-lg border border-brume p-5 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-acier">{label}</div>
            <div className="mt-1 text-2xl font-bold">{value}</div>
        </div>
    );
}

function Bars({ rows, valueKey = 'total', labelKey = 'bucket' }) {
    const max = Math.max(1, ...rows.map((r) => Number(r[valueKey]) || 0));
    return (
        <div className="space-y-1.5">
            {rows.map((r) => (
                <div key={r[labelKey]} className="flex items-center gap-3 text-sm">
                    <span className="w-24 shrink-0 font-mono text-acier truncate">{r[labelKey]}</span>
                    <div className="flex-1 bg-brume/40 rounded">
                        <div
                            className="h-4 rounded bg-cobalt-500"
                            style={{ width: `${((Number(r[valueKey]) || 0) / max) * 100}%` }}
                        />
                    </div>
                    <span className="w-24 shrink-0 text-right tabular-nums">
                        {r[valueKey]}
                        {r.uniques !== undefined && (
                            <span className="text-acier"> · {r.uniques} uniq.</span>
                        )}
                    </span>
                </div>
            ))}
            {rows.length === 0 && <p className="py-4 text-acier text-sm">Pas encore de données.</p>}
        </div>
    );
}

function Table({ title, rows, labelKey, labelHead, mono = false }) {
    return (
        <div className="bg-white rounded-lg border border-brume p-5 shadow-sm">
            <h2 className="font-semibold">{title}</h2>
            <table className="w-full mt-3 text-sm">
                <thead className="text-left text-acier">
                    <tr>
                        <th className="py-2">{labelHead}</th>
                        <th className="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((r, i) => (
                        <tr key={`${r[labelKey]}-${i}`} className="border-t border-slate-100">
                            <td className={`py-2 truncate ${mono ? 'font-mono' : ''}`}>{r[labelKey] || '—'}</td>
                            <td className="text-right tabular-nums">{r.total}</td>
                        </tr>
                    ))}
                    {rows.length === 0 && (
                        <tr><td colSpan={2} className="py-4 text-acier">Pas encore de données.</td></tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}

export default function Stats({
    period = 'day',
    stats = {},
    visitsByBucket = [],
    provenance = [],
    topReferers = [],
    topClicks = [],
    clicksByPage = [],
}) {
    return (
        <AdminLayout title="Statistiques">
            <div className="flex items-center gap-2 mb-6">
                {PERIODS.map((p) => (
                    <Link
                        key={p.key}
                        href={`/admin/stats?period=${p.key}`}
                        preserveScroll
                        className={`rounded-md px-3 py-1.5 text-sm border ${
                            period === p.key
                                ? 'bg-encre text-white border-encre'
                                : 'bg-white text-encre border-brume hover:border-cobalt-400'
                        }`}
                    >
                        {p.label}
                    </Link>
                ))}
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <Stat label="Visites (période)" value={stats.visits ?? 0} />
                <Stat label="Visiteurs uniques" value={stats.uniques ?? 0} />
                <Stat label="Clics (période)" value={stats.clicks ?? 0} />
            </div>

            <div className="bg-white rounded-lg border border-brume p-5 shadow-sm mt-8">
                <h2 className="font-semibold mb-4">Visites {PERIODS.find((p) => p.key === period)?.label.toLowerCase()}</h2>
                <Bars rows={visitsByBucket} />
            </div>

            <div className="grid gap-6 md:grid-cols-2 mt-6">
                <Table title="Provenance du trafic" rows={provenance} labelKey="category" labelHead="Canal" />
                <Table title="Sites référents" rows={topReferers} labelKey="host" labelHead="Domaine" mono />
            </div>

            <div className="grid gap-6 md:grid-cols-2 mt-6">
                <Table title="Clics par cible (boutons, CTA, liens)" rows={topClicks} labelKey="name" labelHead="Cible" mono />
                <Table title="Clics par page (dont articles & prestations)" rows={clicksByPage} labelKey="url" labelHead="Page" mono />
            </div>
        </AdminLayout>
    );
}
