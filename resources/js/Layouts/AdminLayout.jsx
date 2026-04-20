import { Head, Link, router } from '@inertiajs/react';

export default function AdminLayout({ title, children }) {
    return (
        <div className="min-h-screen bg-slate-100 text-slate-800">
            <Head title={title ? `${title} — Admin Kodem` : 'Admin Kodem'} />
            <header className="bg-slate-900 text-white">
                <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-6">
                        <Link href="/admin" className="font-semibold flex items-center gap-2">
                            <span className="inline-block w-6 h-6 rounded-md bg-gradient-to-br from-indigo-400 to-sky-400" aria-hidden="true" />
                            Kodem Admin
                        </Link>
                        <nav className="flex items-center gap-5 text-sm">
                            <Link href="/admin" className="hover:text-indigo-300">Tableau de bord</Link>
                            <Link href="/admin/audits" className="hover:text-indigo-300">Audits</Link>
                            <Link href="/admin/messages" className="hover:text-indigo-300">Messages</Link>
                            <Link href="/admin/events" className="hover:text-indigo-300">Événements</Link>
                        </nav>
                    </div>
                    <div className="flex items-center gap-3 text-sm">
                        <Link href="/" className="text-slate-300 hover:text-white">Site public</Link>
                        <button
                            onClick={() => router.post('/logout')}
                            className="rounded-md bg-slate-800 hover:bg-slate-700 px-3 py-1.5"
                        >
                            Déconnexion
                        </button>
                    </div>
                </div>
            </header>
            <main className="max-w-7xl mx-auto px-6 py-8">
                {title && <h1 className="text-2xl font-bold mb-6">{title}</h1>}
                {children}
            </main>
        </div>
    );
}
