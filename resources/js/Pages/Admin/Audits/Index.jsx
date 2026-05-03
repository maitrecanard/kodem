import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ audits }) {
    return (
        <AdminLayout title="Audits">
            <div className="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th className="px-4 py-3">URL</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3">SEO</th>
                            <th className="px-4 py-3">Sécurité</th>
                            <th className="px-4 py-3">Total</th>
                            <th className="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        {audits.data.map((a) => (
                            <tr key={a.uuid} className="border-t border-slate-100">
                                <td className="px-4 py-2">
                                    <Link href={`/admin/audits/${a.uuid}`} className="text-indigo-700 hover:underline break-all">{a.url}</Link>
                                </td>
                                <td className="px-4 py-2">{a.status}</td>
                                <td className="px-4 py-2">{a.score_seo ?? '—'}</td>
                                <td className="px-4 py-2">{a.score_security ?? '—'}</td>
                                <td className="px-4 py-2 font-semibold">{a.score_total ?? '—'}</td>
                                <td className="px-4 py-2 text-slate-500">{a.created_at?.slice(0, 10)}</td>
                            </tr>
                        ))}
                        {audits.data.length === 0 && (
                            <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-400">Aucun audit.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
