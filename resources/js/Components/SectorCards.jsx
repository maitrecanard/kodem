import { Link } from '@inertiajs/react';

/**
 * @param {{ secteurs: Array<{nom: string, processus: string, preuve?: string, cas_lie?: string}> }} props
 */
export default function SectorCards({ secteurs }) {
    return (
        <ul className="kodem-reveal-grid kodem-reveal-grid--3 mt-8 grid gap-6 md:grid-cols-3">
            {secteurs.map((secteur) => (
                <li key={secteur.nom} className="rounded-kodem border border-brume bg-white p-6">
                    <h3 className="text-kodem-h2 font-semibold text-encre">{secteur.nom}</h3>
                    <p className="mt-3 text-sm text-acier">{secteur.processus}</p>
                    {secteur.preuve && <p className="mt-4 font-mono text-legende text-acier">// {secteur.preuve}</p>}
                    {secteur.cas_lie && (
                        <Link
                            href={`/realisations/${secteur.cas_lie}`}
                            aria-label={`Voir le cas : ${secteur.nom}`}
                            className="mt-4 inline-block text-cobalt-600 hover:text-cobalt-800 text-sm font-medium"
                        >
                            Voir le cas →
                        </Link>
                    )}
                </li>
            ))}
        </ul>
    );
}
