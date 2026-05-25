import { useRef } from 'react';
import { useForm } from '@inertiajs/react';

export default function Premium({ price_cents, currency }) {
    const formLoadedAt = useRef(Math.floor(Date.now() / 1000));

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        domain: '',
        options: { seo: true, security: true },
        rgpd_consent: false,
        website: '',                          // honeypot
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
    });

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <h1>Audit premium — {priceFormatted}</h1>

            <input
                type="text"
                placeholder="Nom"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
            />
            {errors.name && <p className="error">{errors.name}</p>}

            <input
                type="email"
                placeholder="Email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
            />
            {errors.email && <p className="error">{errors.email}</p>}

            <input
                type="text"
                placeholder="exemple.fr"
                value={data.domain}
                onChange={(e) => setData('domain', e.target.value)}
            />
            {errors.domain && <p className="error">{errors.domain}</p>}

            <label>
                <input
                    type="checkbox"
                    checked={data.options.seo}
                    onChange={(e) =>
                        setData('options', { ...data.options, seo: e.target.checked })
                    }
                />
                Audit SEO
            </label>

            <label>
                <input
                    type="checkbox"
                    checked={data.options.security}
                    onChange={(e) =>
                        setData('options', { ...data.options, security: e.target.checked })
                    }
                />
                Audit sécurité
            </label>

            <label>
                <input
                    type="checkbox"
                    checked={data.rgpd_consent}
                    onChange={(e) => setData('rgpd_consent', e.target.checked)}
                />
                J'accepte le traitement de mes données
            </label>
            {errors.rgpd_consent && <p className="error">{errors.rgpd_consent}</p>}

            {/* HONEYPOT — caché en CSS, doit rester vide */}
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

            {errors.stripe && <p className="error">{errors.stripe}</p>}

            <button type="submit" disabled={processing}>
                {processing ? 'Redirection vers Stripe...' : `Payer ${priceFormatted}`}
            </button>
        </form>
    );
}
