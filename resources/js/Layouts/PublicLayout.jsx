import { Head, Link } from '@inertiajs/react';
import { trackClick } from '@/lib/track';

export default function PublicLayout({ meta, children }) {
    const title = meta?.title || 'Kodem — Développement web, hébergement et audits SEO/sécurité';
    const description =
        meta?.description ||
        "Kodem, société de développement web, création de SaaS, hébergement web et audits SEO et de sécurité automatisés.";
    const keywords =
        meta?.keywords ||
        'audit SEO, audit de sécurité, développement web, hébergement web, création de saas';

    return (
        <div className="min-h-screen flex flex-col bg-slate-50 text-slate-800 antialiased">
            <Head>
                <title>{title}</title>
                <meta name="description" content={description} />
                <meta name="keywords" content={keywords} />
                <meta name="robots" content="index, follow" />
                <meta property="og:title" content={title} />
                <meta property="og:description" content={description} />
                <meta property="og:type" content="website" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={title} />
                <meta name="twitter:description" content={description} />
            </Head>

            <header className="bg-white border-b border-slate-200 sticky top-0 z-30">
                <div className="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2 font-semibold text-lg">
                        <span className="inline-block w-8 h-8 rounded-md bg-gradient-to-br from-indigo-600 to-sky-500" aria-hidden="true" />
                        <span>Kodem</span>
                    </Link>
                    <nav className="hidden md:flex items-center gap-6 text-sm">
                        <Link href="/" onClick={() => trackClick('nav_home')} className="hover:text-indigo-600">Accueil</Link>
                        <Link href="/prestations" onClick={() => trackClick('nav_services')} className="hover:text-indigo-600">Prestations</Link>
                        <Link href="/audit" onClick={() => trackClick('nav_audit')} className="hover:text-indigo-600">Audit en ligne</Link>
                        <Link href="/contact" onClick={() => trackClick('nav_contact')} className="hover:text-indigo-600">Contact</Link>
                    </nav>
                    <Link
                        href="/audit"
                        onClick={() => trackClick('header_cta_audit')}
                        className="hidden md:inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700"
                    >
                        Lancer un audit
                    </Link>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="bg-slate-900 text-slate-300 mt-16">
                <div className="max-w-6xl mx-auto px-6 py-12 grid gap-8 md:grid-cols-4">
                    <div>
                        <div className="flex items-center gap-2 font-semibold text-white mb-3">
                            <span className="inline-block w-6 h-6 rounded-md bg-gradient-to-br from-indigo-500 to-sky-400" aria-hidden="true" />
                            Kodem
                        </div>
                        <p className="text-sm text-slate-400">
                            Développement web, création de SaaS, hébergement web et audits SEO / sécurité automatisés.
                        </p>
                    </div>
                    <div>
                        <h3 className="text-white font-semibold mb-3">Prestations</h3>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="/prestations" className="hover:text-white">Développement web</Link></li>
                            <li><Link href="/prestations" className="hover:text-white">Création de SaaS</Link></li>
                            <li><Link href="/prestations" className="hover:text-white">Hébergement web</Link></li>
                            <li><Link href="/audit" className="hover:text-white">Audit SEO</Link></li>
                            <li><Link href="/audit" className="hover:text-white">Audit de sécurité</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-white font-semibold mb-3">Société</h3>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="/contact" className="hover:text-white">Contact</Link></li>
                            <li><Link href="/mentions-legales" className="hover:text-white">Mentions légales</Link></li>
                            <li><Link href="/cgv" className="hover:text-white">CGV</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 className="text-white font-semibold mb-3">Contact</h3>
                        <p className="text-sm">contact@kodem.fr</p>
                        <p className="text-sm text-slate-400 mt-2">Réponse sous 24h ouvrées.</p>
                    </div>
                </div>
                <div className="border-t border-slate-800">
                    <div className="max-w-6xl mx-auto px-6 py-4 text-xs text-slate-500 flex items-center justify-between">
                        <span>© {new Date().getFullYear()} Kodem. Tous droits réservés.</span>
                        <span>Fabriqué avec Laravel + React + Vite</span>
                    </div>
                </div>
            </footer>
        </div>
    );
}
