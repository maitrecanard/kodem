import { Head, Link, router } from '@inertiajs/react';
import BrandWordmark from '@/Components/BrandWordmark';

export default function AdminLayout({ title, children }) {
    return (
        <div className="min-h-screen bg-papier text-encre">
            <Head title={title ? `${title} — Admin Kodem` : 'Admin Kodem'} />
            <header className="bg-encre text-white">
                <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-6">
                        <Link href="/admin" className="flex items-center gap-2">
                            <BrandWordmark className="text-lg text-white" bracketClassName="text-cobalt-400" />
                            <span className="font-mono text-xs uppercase tracking-widest text-cobalt-300">admin</span>
                        </Link>
                        <nav className="flex items-center gap-5 text-sm text-brume">
                            <Link href="/admin" className="hover:text-cobalt-300">Tableau de bord</Link>
                            <Link href="/admin/audits" className="hover:text-cobalt-300">Audits</Link>
                            <Link href="/admin/messages" className="hover:text-cobalt-300">Messages</Link>
                            <Link href="/admin/events" className="hover:text-cobalt-300">Événements</Link>
                        </nav>
                    </div>
                    <div className="flex items-center gap-3 text-sm">
                        <Link href="/" className="text-brume hover:text-white">Site public</Link>
                        <button
                            onClick={() => router.post('/logout')}
                            className="rounded-md bg-cobalt-600 hover:bg-cobalt-700 px-3 py-1.5 text-white"
                        >
                            Déconnexion
                        </button>
                    </div>
                </div>
            </header>
            <main className="max-w-7xl mx-auto px-6 py-8">
                {title && <h1 className="text-2xl font-bold mb-6 text-encre">{title}</h1>}
                {children}
            </main>
        </div>
    );
}
