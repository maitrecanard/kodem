import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Challenge() {
    const { data, setData, post, processing, errors } = useForm({ code: '' });
    const submit = (e) => { e.preventDefault(); post('/admin/2fa/verify'); };

    return (
        <AdminLayout title="Vérification 2FA">
            <div className="bg-white rounded-lg border border-slate-200 p-6 shadow-sm max-w-md">
                <p className="text-sm text-slate-600">
                    Saisissez le code à 6 chiffres généré par votre application d'authentification.
                </p>
                <form onSubmit={submit} className="mt-6">
                    <input
                        type="text"
                        inputMode="numeric"
                        pattern="\d*"
                        maxLength={6}
                        autoFocus
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                        className="block w-full border border-slate-300 rounded px-3 py-2 font-mono text-lg tracking-widest"
                    />
                    {errors.code && <div className="mt-1 text-sm text-rose-600">{errors.code}</div>}
                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-4 w-full rounded bg-indigo-600 text-white px-4 py-2 font-medium hover:bg-indigo-700 disabled:opacity-60"
                    >
                        Valider
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
