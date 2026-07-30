import { Head, Link, usePage } from '@inertiajs/react';
import { trackClick } from '@/lib/track';
import BrandWordmark from '@/Components/BrandWordmark';
import ContactBlockMono from '@/Components/ContactBlockMono';

export default function PublicLayout({ meta, children }) {
    const title = meta?.title || 'Kodem — Dispositifs connectés sur site, partout en France';
    const description =
        meta?.description ||
        "Kodem conçoit, déploie et sécurise des dispositifs logiciels connectés sur site — bornes, écrans pilotés, installations interactives — partout en France.";

    // URL absolue de la page courante (partagée par Inertia via Ziggy) pour
    // canonical + Open Graph. L'origine sert à construire l'URL absolue de l'image OG.
    const location = usePage().props?.ziggy?.location || '';
    let origin = '';
    try {
        origin = location ? new URL(location).origin : '';
    } catch {
        origin = '';
    }
    const canonical = meta?.canonical || location;
    const ogImage = `${origin}/og-image.png`;

    return (
        <div className="min-h-screen flex flex-col bg-papier text-encre antialiased">
            <Head>
                <title>{title}</title>
                <meta name="description" content={description} />
                {/* Indexable par défaut ; une page peut s'en exclure via meta.robots
                    (retours de paiement, pages de confirmation…). */}
                <meta name="robots" content={meta?.robots || 'index, follow'} />
                {canonical && <link rel="canonical" href={canonical} />}

                {/* Open Graph */}
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="Kodem" />
                <meta property="og:locale" content="fr_FR" />
                <meta property="og:title" content={title} />
                <meta property="og:description" content={description} />
                {canonical && <meta property="og:url" content={canonical} />}
                <meta property="og:image" content={ogImage} />

                {/* Twitter Card */}
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={title} />
                <meta name="twitter:description" content={description} />
                <meta name="twitter:image" content={ogImage} />
            </Head>

            <header className="bg-white border-b border-brume sticky top-0 z-30">
                <div className="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                    <Link href="/" className="flex items-center">
                        <BrandWordmark className="text-2xl text-encre" />
                    </Link>
                    <div className="flex items-center gap-6">
                        <nav className="hidden md:flex items-center gap-6 text-sm text-acier">
                            <Link href="/realisations" onClick={() => trackClick('nav_realisations')} className="kodem-link hover:text-cobalt-600 transition-colors duration-[var(--kodem-dur-2)]">Réalisations</Link>
                            <Link href="/expertises" onClick={() => trackClick('nav_expertises')} className="kodem-link hover:text-cobalt-600 transition-colors duration-[var(--kodem-dur-2)]">Expertises</Link>
                            <Link href="/notes" onClick={() => trackClick('nav_notes')} className="kodem-link hover:text-cobalt-600 transition-colors duration-[var(--kodem-dur-2)]">Notes</Link>
                            <Link href="/zone-intervention" onClick={() => trackClick('nav_zone_intervention')} className="kodem-link hover:text-cobalt-600 transition-colors duration-[var(--kodem-dur-2)]">Zone d'intervention</Link>
                            <Link href="/contact" onClick={() => trackClick('nav_contact')} className="kodem-link hover:text-cobalt-600 transition-colors duration-[var(--kodem-dur-2)]">Contact</Link>
                        </nav>
                        <Link
                            href="/contact"
                            onClick={() => trackClick('header_cta_contact')}
                            className="hidden md:inline-flex items-center rounded-md bg-cobalt-600 px-4 py-2 text-white text-sm font-medium transition-colors duration-[var(--kodem-dur-2)] hover:bg-cobalt-700"
                        >
                            Parler de votre projet
                        </Link>
                    </div>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="bg-encre text-brume mt-16">
                <div className="max-w-6xl mx-auto px-6 py-12 grid gap-8 md:grid-cols-5">
                    <div>
                        <div className="mb-3">
                            <BrandWordmark className="text-xl text-white" bracketClassName="text-cobalt-400" />
                        </div>
                        <p className="text-sm text-acier">
                            Dispositifs logiciels connectés sur site — bornes, écrans pilotés, installations interactives.
                            Développement, hébergement, visibilité et sécurité en support. Basé à Poitiers, intervention partout en France.
                        </p>
                    </div>
                    <div>
                        <h3 className="text-white font-semibold mb-3">Prestations</h3>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="/prestations" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Développement web</Link></li>
                            <li><Link href="/prestations" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Création de SaaS</Link></li>
                            <li><Link href="/hebergement-web" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Hébergement web</Link></li>
                            <li><Link href="/audit" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Audit SEO</Link></li>
                            <li><Link href="/audit" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Audit de sécurité</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-white font-semibold mb-3">Découvrir</h3>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="/realisations" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Réalisations</Link></li>
                            <li><Link href="/expertises" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Expertises</Link></li>
                            <li><Link href="/notes" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Notes techniques</Link></li>
                            <li><Link href="/zone-intervention" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Zone d'intervention</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-white font-semibold mb-3">Société</h3>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="/contact" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Contact</Link></li>
                            <li><Link href="/mentions-legales" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">Mentions légales</Link></li>
                            <li><Link href="/cgv" className="transition-colors duration-[var(--kodem-dur-2)] hover:text-white">CGV</Link></li>
                        </ul>
                    </div>
                    <ContactBlockMono className="bg-transparent p-0" />
                </div>
                <div className="border-t border-cobalt-900">
                    <div className="max-w-6xl mx-auto px-6 py-4 text-xs text-acier flex items-center justify-between">
                        <span>© {new Date().getFullYear()} Kodem. Tous droits réservés.</span>
                        <span>Fabriqué avec Laravel + React + Vite</span>
                    </div>
                </div>
            </footer>
        </div>
    );
}
