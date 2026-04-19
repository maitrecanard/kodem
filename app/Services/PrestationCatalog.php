<?php

namespace App\Services;

class PrestationCatalog
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => 'audit-seo',
                'title' => 'Audit SEO automatisé',
                'price_from' => 0,
                'price_label' => 'Gratuit',
                'tagline' => 'Analyse on-page instantanée de votre site.',
                'description' => 'Audit SEO en ligne gratuit : balises title, meta description, H1, Open Graph, robots.txt, sitemap.xml et performance.',
                'features' => [
                    'Détection des balises SEO essentielles',
                    'Vérification Open Graph / Twitter Cards',
                    'Contrôle robots.txt et sitemap.xml',
                    'Rapport téléchargeable',
                ],
                'cta' => 'Lancer un audit',
                'cta_route' => 'audit.create',
            ],
            [
                'slug' => 'audit-securite',
                'title' => 'Audit de sécurité automatisé',
                'price_from' => 0,
                'price_label' => 'Gratuit',
                'tagline' => 'Diagnostic immédiat des en-têtes de sécurité.',
                'description' => 'Audit de sécurité en ligne : HTTPS, HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, cookies.',
                'features' => [
                    'Analyse HTTPS et certificat',
                    'Vérification des en-têtes de sécurité (CSP, HSTS, ...)',
                    'Détection des cookies non sécurisés',
                    'Note sur 100',
                ],
                'cta' => 'Lancer un audit',
                'cta_route' => 'audit.create',
            ],
            [
                'slug' => 'monitoring',
                'title' => 'Monitoring SEO & sécurité mensuel',
                'price_from' => 49,
                'price_label' => 'À partir de 49€/mois',
                'tagline' => 'Votre site surveillé en continu, rapport mensuel livré.',
                'description' => 'Audit SEO et de sécurité automatisé chaque semaine, alerte e-mail en cas de régression, rapport mensuel PDF.',
                'features' => [
                    'Audits hebdomadaires automatiques',
                    'Alertes en temps réel',
                    'Rapport mensuel PDF',
                    'Historique des scores',
                ],
                'cta' => 'S\'abonner',
                'cta_route' => 'contact',
            ],
            [
                'slug' => 'hebergement-web',
                'title' => 'Hébergement web managé',
                'price_from' => 19,
                'price_label' => 'À partir de 19€/mois',
                'tagline' => 'Hébergement sécurisé, sauvegardes, TLS, monitoring.',
                'description' => 'Hébergement web managé pour vos sites et applications : TLS automatique, sauvegardes chiffrées, WAF, monitoring 24/7.',
                'features' => [
                    'TLS automatique (Let\'s Encrypt)',
                    'Sauvegardes quotidiennes chiffrées',
                    'WAF et protection DDoS',
                    'Monitoring 24/7',
                ],
                'cta' => 'Demander un devis',
                'cta_route' => 'contact',
            ],
            [
                'slug' => 'developpement-web',
                'title' => 'Développement web sur-mesure',
                'price_from' => null,
                'price_label' => 'Sur devis',
                'tagline' => 'Sites, applications et API Laravel + React.',
                'description' => 'Développement web sur-mesure : sites vitrines, applications métier, API. Stack moderne Laravel + React avec SSR.',
                'features' => [
                    'Stack Laravel + React + Vite',
                    'SSR pour le SEO',
                    'Tests automatisés',
                    'Livraison sous 4 à 12 semaines',
                ],
                'cta' => 'Démarrer un projet',
                'cta_route' => 'contact',
            ],
            [
                'slug' => 'creation-saas',
                'title' => 'Création de SaaS clé-en-main',
                'price_from' => null,
                'price_label' => 'Sur devis',
                'tagline' => 'De l\'idée au MVP SaaS déployé et facturé.',
                'description' => 'Création de SaaS complet : authentification, paiement récurrent, admin, multi-tenant, hébergement, support.',
                'features' => [
                    'Auth + 2FA',
                    'Abonnements Stripe',
                    'Admin et statistiques',
                    'Hébergement inclus',
                ],
                'cta' => 'Étudier mon projet',
                'cta_route' => 'contact',
            ],
            [
                'slug' => 'remediation',
                'title' => 'Remédiation assistée',
                'price_from' => 390,
                'price_label' => 'À partir de 390€',
                'tagline' => 'Corrections techniques après audit.',
                'description' => 'Après un audit, nos équipes corrigent les points bloquants identifiés : en-têtes de sécurité, balises SEO, performance.',
                'features' => [
                    'Forfait de 8h',
                    'Rapport de remédiation',
                    'Re-audit inclus',
                    'Garantie résultat',
                ],
                'cta' => 'Commander',
                'cta_route' => 'contact',
            ],
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public static function teaser(): array
    {
        return array_slice(self::all(), 0, 4);
    }
}
