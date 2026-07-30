import { useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import SectionLabel from '@/Components/SectionLabel';
import ContactBlockMono from '@/Components/ContactBlockMono';

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
            <section className="max-w-6xl mx-auto px-6 py-16">
                <SectionLabel>CONTACT</SectionLabel>
                <h1 className="mt-3 text-kodem-h1 font-bold">Parlons de votre projet</h1>
                <p className="animate-kodem-slide mt-3 text-acier">
                    Dispositifs connectés, bornes, écrans pilotés, installations interactives&nbsp;:
                    décrivez votre projet — matériel, lieu, contraintes. Réponse sous 48&nbsp;h.
                </p>

                {(wasSuccessful || flash?.success) && (
                    <div className="mt-6 rounded-kodem border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3">
                        Merci&nbsp;! Votre message a été envoyé, nous revenons rapidement vers vous.
                    </div>
                )}
                {flash?.error && (
                    <div className="mt-6 rounded-kodem border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3">
                        {flash.error}
                    </div>
                )}

                <div className="mt-10 grid md:grid-cols-[1fr,320px] gap-10">
                    <form onSubmit={submit} className="space-y-5 bg-white border border-brume rounded-kodem p-8 shadow-sm">
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
                            className="inline-flex rounded-kodem bg-cobalt-600 text-white px-5 py-3 font-medium hover:bg-cobalt-700 disabled:opacity-60"
                        >
                            {processing ? 'Envoi…' : 'Envoyer le message'}
                        </button>

                        <p className="font-mono text-legende text-acier">
                            // réponse sous 48 h · devis gratuit · interlocuteur unique
                        </p>
                    </form>

                    <ContactBlockMono className="md:sticky md:top-24" />
                </div>

                <style>{`.input{width:100%;border:1px solid #C7D2FB;border-radius:.5rem;padding:.6rem .75rem;font-size:.95rem}.input:focus{outline:none;border-color:#2348E0;box-shadow:0 0 0 3px rgba(35,72,224,.15)}`}</style>
            </section>
        </PublicLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="block text-sm font-medium text-encre mb-1">{label}</span>
            {children}
            {error && <span className="block mt-1 text-sm text-rose-600">{error}</span>}
        </label>
    );
}
