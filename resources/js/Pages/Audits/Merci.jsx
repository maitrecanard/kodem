import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import SectionLabel from '@/Components/SectionLabel';

/**
 * Page de retour après un paiement Stripe réussi.
 *
 * Elle doit être rassurante et surtout non bloquante : le visiteur arrive ici
 * depuis un domaine externe (Stripe), il faut donc lui redonner explicitement
 * une porte de sortie vers le site.
 */
export default function Merci({ meta, delivery_hours, contact_email }) {
    return (
        <PublicLayout meta={meta}>
            <section className="max-w-2xl mx-auto px-6 py-20 text-center">
                <SectionLabel className="justify-center mb-4">COMMANDE CONFIRMÉE</SectionLabel>

                <h1 className="text-4xl md:text-display font-bold leading-[1.05]">
                    Merci pour votre commande
                </h1>

                <p className="animate-kodem-slide mt-6 text-acier">
                    Votre paiement est bien enregistré. Un e-mail de confirmation vous a été
                    envoyé&nbsp;; il contient le lien privé vers votre rapport.
                </p>

                <div className="mt-8 rounded-kodem border border-brume bg-papier p-6 text-left">
                    <p className="font-mono text-legende text-acier">
                        // prochaine étape
                    </p>
                    <p className="mt-2 text-encre">
                        Votre rapport est rédigé à la main et vous sera transmis{' '}
                        <strong>sous {delivery_hours}</strong>. Vous n’avez rien d’autre à faire.
                    </p>
                    {contact_email && (
                        <p className="mt-3 text-sm text-acier">
                            Une question sur votre commande&nbsp;?{' '}
                            <a href={`mailto:${contact_email}`} className="text-cobalt-600 underline">
                                {contact_email}
                            </a>
                        </p>
                    )}
                </div>

                <div className="mt-10 flex flex-wrap gap-3 justify-center">
                    <Link
                        href="/"
                        className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                    >
                        Retour au site
                    </Link>
                    <Link
                        href="/realisations"
                        className="inline-flex items-center rounded-kodem border border-brume px-5 py-3 font-medium hover:bg-brume transition"
                    >
                        Voir les réalisations
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
