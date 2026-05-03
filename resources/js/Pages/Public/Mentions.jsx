import PublicLayout from '@/Layouts/PublicLayout';

export default function Mentions({ meta }) {
    return (
        <PublicLayout meta={meta}>
            <section className="max-w-3xl mx-auto px-6 py-16 prose prose-slate">
                <h1>Mentions légales</h1>
                <h2>Éditeur du site</h2>
                <p>
                    Le présent site est édité par la société <strong>Kodem</strong>, société spécialisée en développement web,
                    création de SaaS, hébergement web et audits SEO et de sécurité.
                </p>
                <ul>
                    <li>Raison sociale : Kodem</li>
                    <li>Siège social : à compléter</li>
                    <li>SIREN / RCS : à compléter</li>
                    <li>Numéro de TVA : à compléter</li>
                    <li>Email : contact@kodem.fr</li>
                    <li>Directeur de publication : à compléter</li>
                </ul>

                <h2>Hébergement</h2>
                <p>
                    Le site est hébergé par Kodem (infrastructure managée, serveurs en Europe). Toutes les communications
                    sont chiffrées via TLS et les sauvegardes sont chiffrées au repos.
                </p>

                <h2>Propriété intellectuelle</h2>
                <p>
                    L'ensemble des contenus (textes, logos, visuels, code source) est la propriété exclusive de Kodem, sauf
                    mention contraire. Toute reproduction, même partielle, est interdite sans autorisation écrite.
                </p>

                <h2>Données personnelles (RGPD)</h2>
                <p>
                    Les données saisies dans le formulaire de contact et le formulaire d'audit sont stockées en base à des
                    fins de suivi de la demande. Les adresses IP des visiteurs sont anonymisées (hachage SHA-256 salé) pour
                    les besoins statistiques. Vous pouvez exercer vos droits d'accès, de rectification et d'effacement à
                    l'adresse&nbsp;: contact@kodem.fr.
                </p>

                <h2>Cookies</h2>
                <p>
                    Kodem n'utilise pas de cookies publicitaires ni de traceurs tiers. Seuls des cookies techniques
                    strictement nécessaires au fonctionnement du site (session, CSRF) sont déposés.
                </p>
            </section>
        </PublicLayout>
    );
}
