import PublicLayout from '@/Layouts/PublicLayout';

export default function Cgv({ meta }) {
    return (
        <PublicLayout meta={meta}>
            <section className="max-w-3xl mx-auto px-6 py-16 prose prose-slate">
                <h1>Conditions générales de vente</h1>
                <p className="animate-kodem-slide">Version du {new Date().toLocaleDateString('fr-FR')}.</p>

                <h2>1. Objet</h2>
                <p>
                    Les présentes CGV régissent les prestations vendues par Kodem&nbsp;: développement web,
                    création de SaaS, hébergement web, audit SEO et audit de sécurité.
                </p>

                <h2>2. Prestations</h2>
                <p>
                    Chaque prestation fait l'objet d'un devis précisant son périmètre, sa durée, ses livrables et son prix.
                    Les audits en ligne automatisés sont gratuits et livrés immédiatement sous forme de rapport en ligne.
                </p>

                <h2>3. Rapport SEO &amp; sécurité complet</h2>
                <p>
                    Le rapport complet est une prestation <strong>rédigée manuellement</strong>, distincte de l'audit
                    en ligne automatisé. Son prix est de <strong>150 €</strong>, payable à la commande. Il est
                    <strong> transmis dans un délai de 24 à 48 heures</strong> à compter de l'encaissement du
                    paiement, par un lien privé accessible pendant 90 jours.
                </p>
                <p>
                    Cette prestation est <strong>réservée aux professionnels</strong> agissant dans le cadre de leur
                    activité. Le rapport constituant un contenu numérique personnalisé exécuté immédiatement après la
                    commande, il n'ouvre pas droit à rétractation.
                </p>

                <h2>4. Prix et paiement</h2>
                <p>
                    Les prix sont exprimés en euros. <strong>TVA non applicable, article 293 B du Code général des
                    impôts</strong> : aucun montant ne s'ajoute au prix affiché. Les prestations forfaitaires sont
                    payables à la commande. Les abonnements mensuels (monitoring, hébergement) sont prélevés
                    mensuellement, sans engagement de durée. Les paiements sont traités par Stripe.
                </p>

                <h2>5. Exécution</h2>
                <p>
                    Le rapport complet est livré sous 24 à 48 heures (voir article 3). Pour les autres prestations,
                    les délais sont communiqués dans chaque devis. En cas de retard imputable au client (retard de
                    validation, éléments manquants), les délais sont prolongés d'autant.
                </p>

                <h2>6. Garantie et responsabilité</h2>
                <p>
                    Kodem garantit la conformité des livrables au cahier des charges. La responsabilité de Kodem est
                    limitée au montant des prestations facturées sur les 12 derniers mois.
                </p>

                <h2>7. Propriété intellectuelle</h2>
                <p>
                    Le code livré est cédé au client après paiement intégral. Kodem conserve la paternité morale de ses
                    développements et peut référencer les projets dans son portfolio, sauf clause contraire.
                </p>

                <h2>8. Données personnelles</h2>
                <p>
                    Kodem agit en sous-traitant au sens du RGPD lorsqu'elle traite des données personnelles pour le compte
                    du client. Un contrat de sous-traitance peut être fourni sur demande.
                </p>

                <h2>9. Droit applicable</h2>
                <p>
                    Les présentes CGV sont soumises au droit français. Tout litige relèvera des tribunaux compétents du
                    siège social de Kodem, sauf disposition légale contraire.
                </p>
            </section>
        </PublicLayout>
    );
}
