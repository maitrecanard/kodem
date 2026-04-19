import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ audit }) {
    return (
        <AdminLayout title="Audit">
            <div className="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <div className="text-sm text-slate-500">{audit.uuid}</div>
                <h2 className="text-xl font-semibold mt-1 break-all">{audit.url}</h2>
                <p className="mt-2">
                    Statut : <strong>{audit.status}</strong> · SEO : {audit.score_seo ?? '—'} · Sécurité : {audit.score_security ?? '—'} · Total : <strong>{audit.score_total ?? '—'}</strong>/100
                </p>
                {audit.error && <p className="mt-3 text-rose-700">{audit.error}</p>}
                <pre className="mt-6 bg-slate-900 text-slate-100 text-xs p-4 rounded overflow-x-auto">{JSON.stringify(audit.results, null, 2)}</pre>
            </div>
        </AdminLayout>
    );
}
