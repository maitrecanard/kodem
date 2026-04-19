import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ message }) {
    const { data, setData, patch, processing } = useForm({ status: message.status });
    const submit = (e) => { e.preventDefault(); patch(`/admin/messages/${message.id}`); };

    return (
        <AdminLayout title="Message">
            <div className="bg-white rounded-lg border border-slate-200 p-6 shadow-sm max-w-3xl">
                <h2 className="text-xl font-semibold">{message.subject}</h2>
                <p className="text-sm text-slate-500 mt-1">De : {message.name} &lt;{message.email}&gt; {message.company ? `· ${message.company}` : ''}</p>
                <p className="text-xs text-slate-400 mt-1">Reçu le {new Date(message.created_at).toLocaleString('fr-FR')}</p>
                <div className="mt-6 whitespace-pre-wrap border-l-4 border-indigo-200 pl-4 text-slate-700">{message.message}</div>
                <form onSubmit={submit} className="mt-6 flex items-center gap-3">
                    <label className="text-sm">Statut :</label>
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        className="border border-slate-300 rounded px-2 py-1"
                    >
                        <option value="new">Nouveau</option>
                        <option value="read">Lu</option>
                        <option value="replied">Répondu</option>
                        <option value="archived">Archivé</option>
                    </select>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded bg-indigo-600 text-white px-3 py-1.5 text-sm hover:bg-indigo-700 disabled:opacity-60"
                    >
                        Enregistrer
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
