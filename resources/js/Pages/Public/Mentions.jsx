import { usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Mentions({ meta }) {
    const { contactEmail } = usePage().props;

    return (
        <PublicLayout meta={meta}>
            <section className="max-w-3xl mx-auto px-6 py-16 prose prose-slate">
                <h1>Mentions légales</h1>
                <h2>Éditeur du site</h2>
                <p>
                    Le présent site est édité par <strong>Mathieu Siaudeau</strong>, entrepreneur individuel exerçant
                    sous le nom commercial <strong>KODEM</strong> (conception d'outils métier sur mesure).
                </p>
                <ul>
                    <li>Nom : Mathieu Siaudeau, entrepreneur individuel (EI)</li>
                    <li>Nom commercial : KODEM</li>
                    <li>Adresse : 13 Résidence de la Picauderie, 86300 Valdivienne, France</li>
                    <li>SIREN : 922 818 547</li>
                    <li>Email : {contactEmail}</li>
                    <li>Téléphone : 07 62 61 26 46</li>
                    <li>Directeur de la publication : Mathieu Siaudeau</li>
                </ul>

                <h2>Hébergement</h2>
                <p>Le site est hébergé sur un serveur dédié loué auprès de :</p>
                <ul>
                    <li>OVH SAS</li>
                    <li>2 rue Kellermann, 59100 Roubaix, France</li>
                    <li>Téléphone : 1007</li>
                </ul>

                <h2>Propriété intellectuelle</h2>
                <p>
                    L'ensemble des contenus (textes, logos, visuels, code source) est la propriété exclusive de Mathieu Siaudeau (KODEM), sauf
                    mention contraire. Toute reproduction, même partielle, est interdite sans autorisation écrite.
                </p>

                <h2>Données personnelles (RGPD)</h2>
                <p>
                    Les données saisies dans le formulaire de contact et le formulaire d'audit sont stockées en base à des
                    fins de suivi de la demande. Les adresses IP des visiteurs sont anonymisées (hachage SHA-256 salé) pour
                    les besoins statistiques. Vous pouvez exercer vos droits d'accès, de rectification et d'effacement à
                    l'adresse&nbsp;: {contactEmail}.
                </p>

                <h2>Cookies</h2>
                <p>
                    KODEM n'utilise pas de cookies publicitaires ni de traceurs tiers. Seuls des cookies techniques
                    strictement nécessaires au fonctionnement du site (session, CSRF) sont déposés.
                </p>
            </section>
        </PublicLayout>
    );
}
