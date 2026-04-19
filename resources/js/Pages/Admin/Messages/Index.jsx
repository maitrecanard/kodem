import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ messages }) {
    return (
        <AdminLayout title="Messages">
            <div className="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th className="px-4 py-3">Sujet</th>
                            <th className="px-4 py-3">Expéditeur</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        {messages.data.map((m) => (
                            <tr key={m.id} className="border-t border-slate-100">
                                <td className="px-4 py-2">
                                    <Link href={`/admin/messages/${m.id}`} className="text-indigo-700 hover:underline">{m.subject}</Link>
                                </td>
                                <td className="px-4 py-2">{m.name} &lt;{m.email}&gt;</td>
                                <td className="px-4 py-2"><span className="text-xs rounded-full bg-slate-100 px-2 py-0.5">{m.status}</span></td>
                                <td className="px-4 py-2 text-slate-500">{m.created_at?.slice(0, 10)}</td>
                            </tr>
                        ))}
                        {messages.data.length === 0 && (
                            <tr><td colSpan={4} className="px-4 py-6 text-center text-slate-400">Aucun message.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
