import PublicLayout from '@/Layouts/PublicLayout';
import { Link } from '@inertiajs/react';

export default function AuditFollowupUnsubscribed({ meta, audit, already }) {
    return (
        <PublicLayout meta={meta}>
            <section className="max-w-2xl mx-auto px-6 py-16">
                <h1 className="text-3xl font-bold text-slate-900">
                    {already ? 'Vous étiez déjà désinscrit' : 'Désinscription confirmée'}
                </h1>
                <p className="mt-4 text-slate-700">
                    Aucune relance commerciale ne sera envoyée pour l'audit de{' '}
                    <strong>{audit.url}</strong>.
                </p>
                <p className="mt-2 text-slate-600 text-sm">
                    Le rapport reste accessible et vous pouvez le consulter à tout moment.
                </p>
                <div className="mt-8 flex gap-3">
                    <Link
                        href={`/audit/${audit.uuid}`}
                        className="inline-block px-4 py-2 rounded bg-indigo-600 text-white font-medium hover:bg-indigo-700"
                    >
                        Voir mon rapport
                    </Link>
                    <Link
                        href="/contact"
                        className="inline-block px-4 py-2 rounded bg-slate-100 text-slate-800 font-medium hover:bg-slate-200"
                    >
                        Nous contacter
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
