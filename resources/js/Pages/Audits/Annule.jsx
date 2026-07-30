import { Link } from '@inertiajs/react';

export default function Annule() {
    return (
        <div className="text-center py-16">
            <h1>Paiement annulé</h1>
            <p className="animate-kodem-slide">Aucun montant n'a été prélevé.</p>
            <Link href={route('audits.premium')}>Retour au formulaire</Link>
        </div>
    );
}
