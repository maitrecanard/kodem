import { useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import FormField, { FormInputStyle } from '@/Components/FormField';

// Arbitrage de l'actionnaire du 2026-11-13 : page d'annonce, sans prix ni date de lancement.
const SUJET = 'Publicités IA : être prévenu du lancement';
const MESSAGE_PAR_DEFAUT = 'Je souhaite être prévenu du lancement des publicités vidéo et photo générées par IA.';

const ATOUTS = [
    {
        label: 'VOS PHOTOS',
        texte: "Chaque plan est animé à partir de vos propres photos : votre lieu, vos produits, pas une image de banque.",
    },
    {
        label: 'PRÊTE À PUBLIER',
        texte: 'Une vidéo courte avec voix off en français, vos textes à l’écran et votre logo, pour vos réseaux et votre site.',
    },
    {
        label: 'TRANSPARENTE',
        texte: 'Chaque vidéo porte la mention « Contenu généré par IA », comme l’impose le règlement européen sur l’IA.',
    },
];

export default function PubIa({ meta, video }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                title="Bientôt : vos publicités vidéo et photo générées par IA"
                cta={
                    <a
                        href="#prevenu"
                        onClick={() => trackClick('pub_ia_hero_prevenu')}
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Être prévenu du lancement
                    </a>
                }
            />

            <section className="max-w-6xl mx-auto px-6 pt-16 pb-8">
                <SectionLabel>BIENTÔT</SectionLabel>
                <h2 className="mt-4 text-kodem-h1 font-bold max-w-2xl">
                    Une vidéo publicitaire à partir des photos de votre établissement.
                </h2>
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    Commerce, hébergement touristique, artisan ou PME : KODEM transforme vos photos en une courte
                    vidéo publicitaire animée par IA, puis en visuels pour vos réseaux. L'offre ouvre prochainement ;
                    laissez votre adresse pour être parmi les premiers prévenus.
                </p>
            </section>

            <section className="max-w-6xl mx-auto px-6 pb-16">
                <div className="kodem-reveal-grid kodem-reveal-grid--3 grid gap-6 md:grid-cols-3">
                    {ATOUTS.map((atout) => (
                        <div key={atout.label} className="bg-white rounded-kodem border border-brume p-6 shadow-sm">
                            <SectionLabel>{atout.label}</SectionLabel>
                            <p className="mt-4 text-acier text-sm leading-relaxed">{atout.texte}</p>
                        </div>
                    ))}
                </div>
            </section>

            {video && <Demonstration video={video} />}

            <Prevenu />
        </PublicLayout>
    );
}

function Demonstration({ video }) {
    return (
        <section className="bg-white border-y border-brume">
            <div className="max-w-6xl mx-auto px-6 py-16">
                <SectionLabel>EXEMPLE</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold max-w-2xl">Une vidéo réalisée pour Localoge</h2>
                <video
                    className="mt-8 w-full max-w-4xl rounded-kodem border border-brume bg-encre"
                    src={video.src}
                    poster={video.poster ?? undefined}
                    controls
                    playsInline
                    preload="none"
                    onPlay={() => trackClick('pub_ia_demo_lecture')}
                />
                <p className="mt-3 max-w-4xl text-sm text-acier">
                    Photos d'annonces réelles publiées sur Localoge, animées par IA. Contenu généré par IA.
                    Voix&nbsp;: Kyutai TTS (CC BY 4.0).
                </p>
            </div>
        </section>
    );
}

function Prevenu() {
    const { data, setData, post, processing, errors, reset, wasSuccessful } = useForm({
        name: '',
        email: '',
        company: '',
        subject: SUJET,
        message: MESSAGE_PAR_DEFAUT,
        website: '', // honeypot
    });

    const submit = (e) => {
        e.preventDefault();
        trackClick('pub_ia_prevenu_envoi');
        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset('name', 'email', 'company'),
        });
    };

    return (
        <section id="prevenu" className="kodem-reveal max-w-6xl mx-auto px-6 py-20 scroll-mt-24">
            <div className="grid md:grid-cols-2 gap-12 items-start">
                <div>
                    <SectionLabel>LANCEMENT</SectionLabel>
                    <h2 className="mt-3 text-kodem-h1 font-bold">Être prévenu du lancement</h2>
                    <p className="mt-4 text-acier leading-relaxed">
                        Un seul message, à l'ouverture de l'offre. Votre adresse ne sert qu'à cela.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-5 bg-white border border-brume rounded-kodem p-8 shadow-sm">
                    {wasSuccessful && (
                        <div className="rounded-kodem border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3">
                            Merci&nbsp;! Vous serez prévenu dès le lancement.
                        </div>
                    )}

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

                    <FormField label="Nom" error={errors.name}>
                        <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} required className="input" />
                    </FormField>
                    <FormField label="Email" error={errors.email}>
                        <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required className="input" />
                    </FormField>
                    <FormField label="Établissement (facultatif)" error={errors.company}>
                        <input type="text" value={data.company} onChange={(e) => setData('company', e.target.value)} className="input" />
                    </FormField>
                    <FormField label="Message" error={errors.message ?? errors.subject}>
                        <textarea rows={3} value={data.message} onChange={(e) => setData('message', e.target.value)} required className="input" />
                    </FormField>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex rounded-kodem bg-cobalt-600 text-white px-5 py-3 font-medium hover:bg-cobalt-700 disabled:opacity-60"
                    >
                        {processing ? 'Envoi…' : 'Me prévenir'}
                    </button>
                </form>
            </div>

            <FormInputStyle />
        </section>
    );
}
