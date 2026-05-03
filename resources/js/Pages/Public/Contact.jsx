import { useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';

export default function Contact({ meta, flash }) {
    const { data, setData, post, processing, errors, reset, wasSuccessful } = useForm({
        name: '',
        email: '',
        company: '',
        subject: '',
        message: '',
        website: '', // honeypot
    });

    const submit = (e) => {
        e.preventDefault();
        trackClick('contact_submit_button');
        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset('name', 'email', 'company', 'subject', 'message'),
        });
    };

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-3xl mx-auto px-6 py-16">
                <h1 className="text-4xl font-bold">Parlons de votre projet</h1>
                <p className="mt-3 text-slate-600">
                    Développement web, création de SaaS, hébergement web ou audit&nbsp;: décrivez-nous votre besoin,
                    nous répondons sous 24&nbsp;h ouvrées.
                </p>

                {(wasSuccessful || flash?.success) && (
                    <div className="mt-6 rounded-md border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3">
                        Merci&nbsp;! Votre message a été envoyé, nous revenons rapidement vers vous.
                    </div>
                )}
                {flash?.error && (
                    <div className="mt-6 rounded-md border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3">
                        {flash.error}
                    </div>
                )}

                <form onSubmit={submit} className="mt-10 space-y-5 bg-white border border-slate-200 rounded-xl p-8 shadow-sm">
                    {/* Honeypot — invisible aux humains */}
                    <div className="hidden" aria-hidden="true">
                        <label>Ne pas remplir
                            <input
                                type="text"
                                tabIndex={-1}
                                autoComplete="off"
                                value={data.website}
                                onChange={(e) => setData('website', e.target.value)}
                            />
                        </label>
                    </div>

                    <div className="grid md:grid-cols-2 gap-4">
                        <Field label="Nom" error={errors.name}>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                className="input"
                            />
                        </Field>
                        <Field label="Email" error={errors.email}>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                className="input"
                            />
                        </Field>
                    </div>

                    <Field label="Société (facultatif)" error={errors.company}>
                        <input
                            type="text"
                            value={data.company}
                            onChange={(e) => setData('company', e.target.value)}
                            className="input"
                        />
                    </Field>

                    <Field label="Sujet" error={errors.subject}>
                        <input
                            type="text"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            required
                            className="input"
                        />
                    </Field>

                    <Field label="Message" error={errors.message}>
                        <textarea
                            rows={6}
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            required
                            className="input"
                        />
                    </Field>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex rounded-md bg-indigo-600 text-white px-5 py-3 font-medium hover:bg-indigo-700 disabled:opacity-60"
                    >
                        {processing ? 'Envoi…' : 'Envoyer le message'}
                    </button>
                </form>

                <style>{`.input{width:100%;border:1px solid #cbd5e1;border-radius:.5rem;padding:.6rem .75rem;font-size:.95rem}.input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}`}</style>
            </section>
        </PublicLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="block text-sm font-medium text-slate-700 mb-1">{label}</span>
            {children}
            {error && <span className="block mt-1 text-sm text-rose-600">{error}</span>}
        </label>
    );
}
