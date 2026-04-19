import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Setup({ qrSvg, secret, alreadyEnabled }) {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit = (e) => { e.preventDefault(); post('/admin/2fa/enable'); };

    return (
        <AdminLayout title="Activer la 2FA">
            <div className="bg-white rounded-lg border border-slate-200 p-6 shadow-sm max-w-xl">
                <p className="text-sm text-slate-600">
                    Scannez le QR code ci-dessous avec Google Authenticator, Authy, 1Password, etc. puis saisissez le code à 6 chiffres pour confirmer.
                </p>
                <div className="mt-4 flex items-center gap-6">
                    <div className="bg-white border border-slate-200 rounded p-2" dangerouslySetInnerHTML={{ __html: qrSvg }} />
                    <div>
                        <div className="text-xs text-slate-500">Clé secrète (si scan impossible) :</div>
                        <div className="font-mono text-sm break-all mt-1">{secret}</div>
                    </div>
                </div>
                <form onSubmit={submit} className="mt-6">
                    <label className="block text-sm font-medium">Code à 6 chiffres</label>
                    <input
                        type="text"
                        inputMode="numeric"
                        pattern="\d*"
                        maxLength={6}
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                        className="mt-1 block w-full border border-slate-300 rounded px-3 py-2 font-mono text-lg tracking-widest"
                    />
                    {errors.code && <div className="mt-1 text-sm text-rose-600">{errors.code}</div>}
                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-4 rounded bg-indigo-600 text-white px-4 py-2 font-medium hover:bg-indigo-700 disabled:opacity-60"
                    >
                        {alreadyEnabled ? 'Régénérer' : 'Activer la 2FA'}
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
