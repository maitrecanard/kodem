import { useRef } from 'react';
import { useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import SectionLabel from '@/Components/SectionLabel';

/**
 * Page de vente du rapport SEO & sécurité complet.
 *
 * Ce rapport est RÉDIGÉ MANUELLEMENT — ce n'est pas la sortie automatique de
 * l'outil d'audit en ligne. Le prix, le délai de livraison et la mention de TVA
 * viennent tous de config/audits.php : ne rien coder en dur ici.
 */
export default function Premium({
    meta,
    price_cents,
    currency,
    delivery_hours,
    manual,
    audience,
    vat_mention,
}) {
    const formLoadedAt = useRef(Math.floor(Date.now() / 1000));

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        domain: '',
        options: { seo: true, security: true },
        rgpd_consent: false,
        website: '', // honeypot
        form_loaded_at: formLoadedAt.current,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        // Pas de onSuccess ici : la redirection vers Stripe se fait automatiquement
        // grâce à Inertia::location() côté serveur. Inertia détecte le header
        // X-Inertia-Location et fait window.location = url tout seul.
        post(route('audits.premium.checkout'));
    };

    const priceFormatted = (price_cents / 100).toLocaleString('fr-FR', {
        style: 'currency',
        currency: currency.toUpperCase(),
        minimumFractionDigits: 0,
    });

    const inclus = [
        'Analyse SEO technique complète : indexation, structure, balises, contenus, maillage interne.',
        'Analyse de sécurité : en-têtes, exposition de version, configuration TLS, points d’entrée.',
        'Priorisation des correctifs par impact et par effort, pas une liste brute de problèmes.',
        'Recommandations rédigées pour votre site, avec le détail de ce qu’il faut changer.',
        'Un échange pour reprendre le rapport ensemble si un point n’est pas clair.',
    ];

    const champ =
        'w-full rounded-kodem border border-brume px-4 py-3 focus:outline-none focus:ring-2 focus:ring-cobalt-600';

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-5xl mx-auto px-6 py-16">
                <SectionLabel className="mb-4">RAPPORT COMPLET</SectionLabel>
                <h1 className="text-4xl md:text-display font-bold leading-[1.05]">
                    Un rapport SEO &amp; sécurité écrit à la main pour votre site
                </h1>

                <p className="animate-kodem-slide mt-6 text-acier max-w-2xl">
                    L’audit en ligne vous donne un score en quelques secondes. Ce rapport-ci est
                    différent&nbsp;: il est <strong className="text-encre">rédigé manuellement</strong>,
                    site par site, et livré <strong className="text-encre">sous {delivery_hours}</strong>.
                </p>

                <div className="kodem-reveal mt-10 grid md:grid-cols-[1fr,360px] gap-10 items-start">
                    <div>
                        <h2 className="text-xl font-semibold">Ce que contient le rapport</h2>
                        <ul className="mt-4 space-y-3">
                            {inclus.map((item) => (
                                <li key={item} className="flex gap-3 text-acier">
                                    <span aria-hidden="true" className="text-cobalt-600 font-mono">
                                        //
                                    </span>
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-8 rounded-kodem border border-brume bg-papier p-5">
                            <p className="font-mono text-legende text-acier">
                                // {manual ? 'rédaction manuelle' : 'génération automatique'} ·
                                livraison sous {delivery_hours} · réservé aux {audience}
                            </p>
                            <p className="mt-3 text-sm text-acier">
                                Après paiement, vous recevez un e-mail de confirmation. Le rapport
                                vous est ensuite transmis par un lien privé, valable 90 jours.
                            </p>
                        </div>
                    </div>

                    <form
                        onSubmit={handleSubmit}
                        className="bg-white border border-brume rounded-kodem p-6 shadow-sm"
                    >
                        <div className="text-4xl font-bold">{priceFormatted}</div>
                        {vat_mention && (
                            <p className="mt-1 text-xs text-acier">{vat_mention}</p>
                        )}
                        <p className="mt-1 text-sm text-acier">
                            Livraison sous {delivery_hours}
                        </p>

                        <div className="mt-6 space-y-4">
                            <label className="block">
                                <span className="block text-sm font-medium mb-1">Nom</span>
                                <input
                                    type="text"
                                    className={champ}
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                {errors.name && (
                                    <span className="block mt-1 text-sm text-rose-600">{errors.name}</span>
                                )}
                            </label>

                            <label className="block">
                                <span className="block text-sm font-medium mb-1">E-mail</span>
                                <input
                                    type="email"
                                    className={champ}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                {errors.email && (
                                    <span className="block mt-1 text-sm text-rose-600">{errors.email}</span>
                                )}
                            </label>

                            <label className="block">
                                <span className="block text-sm font-medium mb-1">Site à auditer</span>
                                <input
                                    type="text"
                                    className={champ}
                                    placeholder="exemple.fr"
                                    value={data.domain}
                                    onChange={(e) => setData('domain', e.target.value)}
                                    required
                                />
                                {errors.domain && (
                                    <span className="block mt-1 text-sm text-rose-600">{errors.domain}</span>
                                )}
                            </label>

                            <fieldset className="pt-2">
                                <legend className="text-sm font-medium mb-2">Périmètre</legend>
                                <label className="flex items-center gap-2 text-sm text-acier">
                                    <input
                                        type="checkbox"
                                        checked={data.options.seo}
                                        onChange={(e) =>
                                            setData('options', { ...data.options, seo: e.target.checked })
                                        }
                                    />
                                    Référencement (SEO)
                                </label>
                                <label className="flex items-center gap-2 text-sm text-acier mt-2">
                                    <input
                                        type="checkbox"
                                        checked={data.options.security}
                                        onChange={(e) =>
                                            setData('options', {
                                                ...data.options,
                                                security: e.target.checked,
                                            })
                                        }
                                    />
                                    Sécurité
                                </label>
                            </fieldset>

                            <label className="flex items-start gap-2 text-sm text-acier pt-2">
                                <input
                                    type="checkbox"
                                    className="mt-1"
                                    checked={data.rgpd_consent}
                                    onChange={(e) => setData('rgpd_consent', e.target.checked)}
                                />
                                <span>J’accepte le traitement de mes données pour cette commande.</span>
                            </label>
                            {errors.rgpd_consent && (
                                <span className="block text-sm text-rose-600">{errors.rgpd_consent}</span>
                            )}

                            {/* HONEYPOT — invisible, doit rester vide */}
                            <input
                                type="text"
                                name="website"
                                value={data.website}
                                onChange={(e) => setData('website', e.target.value)}
                                tabIndex={-1}
                                autoComplete="off"
                                style={{ position: 'absolute', left: '-9999px' }}
                                aria-hidden="true"
                            />

                            {errors.stripe && (
                                <div role="alert" className="rounded-kodem border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                                    {errors.stripe}
                                </div>
                            )}

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full inline-flex justify-center rounded-kodem bg-cobalt-600 text-white px-5 py-3 font-medium hover:bg-cobalt-700 disabled:opacity-60"
                            >
                                {processing ? 'Redirection…' : `Commander — ${priceFormatted}`}
                            </button>

                            <p className="text-xs text-acier">
                                Paiement sécurisé par Stripe. Prestation réservée aux {audience}.
                            </p>
                        </div>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
