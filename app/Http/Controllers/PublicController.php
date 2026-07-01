<?php

namespace App\Http\Controllers;

use App\Services\PrestationCatalog;
use App\Services\VitrineContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Public/Home', [
            'meta' => [
                'title' => 'Kodem — Création de site internet, logiciel et application web | Hébergement & audits',
                'description' => 'Kodem conçoit votre site internet, application web ou logiciel sur-mesure, avec hébergement web managé et audits SEO et de sécurité automatisés. Testez votre site gratuitement en ligne.',
                'keywords' => 'création site internet, création application web, création logiciel, hébergement web, développement web',
            ],
            'prestations' => PrestationCatalog::teaser(),
        ]);
    }

    public function services(): Response
    {
        return Inertia::render('Public/Services', [
            'meta' => [
                'title' => 'Prestations — Création de site internet, logiciel, application web & hébergement | Kodem',
                'description' => 'Toutes les prestations Kodem : création de site internet, d\'application web et de logiciel sur-mesure, hébergement web managé, audit SEO et audit de sécurité automatisés.',
                'keywords' => 'création site internet, application web, création logiciel, hébergement web, audit SEO, audit de sécurité',
            ],
            'prestations' => PrestationCatalog::all(),
        ]);
    }

    public function hebergement(): Response
    {
        $prestation = collect(PrestationCatalog::all())->firstWhere('slug', 'hebergement-web');

        return Inertia::render('Public/Hebergement', [
            'meta' => [
                'title' => 'Hébergement web managé — sécurisé, sauvegardé, monitoré | Kodem',
                'description' => 'Hébergement web managé pour vos sites et applications : TLS automatique, sauvegardes chiffrées, WAF et monitoring 24/7.',
                'keywords' => 'hébergement web, hébergement web managé, hébergement sécurisé, hébergement application web',
            ],
            'prestation' => $prestation,
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact', [
            'meta' => [
                'title' => 'Contact — Kodem | Agence développement web et hébergement',
                'description' => 'Contactez Kodem pour un projet de développement web, création de SaaS, hébergement web ou audit SEO et de sécurité.',
                'keywords' => 'contact kodem, développement web, hébergement web',
            ],
        ]);
    }

    public function mentions(): Response
    {
        return Inertia::render('Public/Mentions', [
            'meta' => [
                'title' => 'Mentions légales — Kodem',
                'description' => 'Mentions légales de la société Kodem, société de développement web et d\'hébergement.',
                'keywords' => 'mentions légales, Kodem',
            ],
        ]);
    }

    public function cgv(): Response
    {
        return Inertia::render('Public/Cgv', [
            'meta' => [
                'title' => 'Conditions générales de vente — Kodem',
                'description' => 'Conditions générales de vente des prestations Kodem : développement web, hébergement, audits SEO et sécurité.',
                'keywords' => 'CGV, conditions générales de vente, Kodem',
            ],
        ]);
    }

    public function realisations(): Response
    {
        return Inertia::render('Public/Realisations', [
            'meta' => [
                'title' => 'Réalisations — dispositifs connectés sur site déployés par Kodem',
                'description' => 'Cas clients Kodem : photomaton événementiel et écran piloté par Raspberry Pi. Problème, contrainte technique, choix retenu, résultat chiffré.',
            ],
            'positioning' => VitrineContent::positioning(),
            'cases' => VitrineContent::cases(),
            'jsonLd' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Accueil',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Réalisations',
                            'item' => route('realisations.index'),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function realisationShow(string $slug): Response
    {
        $cas = VitrineContent::case($slug);
        abort_unless($cas, 404);

        return Inertia::render('Public/RealisationShow', [
            'meta' => [
                'title' => $cas['titre'].' — Réalisation Kodem',
                'description' => $cas['resume'],
            ],
            'cas' => $cas,
            'testimonials' => VitrineContent::testimonialsForCase($slug),
            'jsonLd' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Accueil',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Réalisations',
                            'item' => route('realisations.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $cas['titre'],
                            'item' => route('realisations.show', $slug),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function expertises(): Response
    {
        return Inertia::render('Public/Expertises', [
            'meta' => [
                'title' => 'Capacités techniques — développement, hébergement, SEO, sécurité | Kodem',
                'description' => 'Développement embarqué, hébergement, SEO local et sécurité : capacités de support des dispositifs connectés sur site déployés par Kodem en Nouvelle-Aquitaine.',
            ],
            'positioning' => VitrineContent::positioning(),
            'jsonLd' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Accueil',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Expertises',
                            'item' => route('expertises'),
                        ],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Quelle est la différence entre un dispositif connecté sur site et une solution cloud générique ?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Un dispositif sur site fonctionne localement — pas de dépendance internet pendant l\'utilisation. Kodem assure le déploiement physique, la configuration sur le matériel du client et la supervision à distance.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Sur quel matériel Kodem intervient-il ?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Raspberry Pi, mini-PC x86, bornes custom, écrans industriels. Le choix du matériel dépend des contraintes d\'environnement et du budget. Kodem conseille et déploie.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Le logiciel est-il maintenable après la livraison ?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Oui. Kodem assure les mises à jour, le monitoring et les interventions correctives. Le code source appartient au client.',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function zoneIntervention(): Response
    {
        return Inertia::render('Public/ZoneIntervention', [
            'meta' => [
                'title' => 'Zone d\'intervention — Kodem à Poitiers, Nouvelle-Aquitaine',
                'description' => 'Kodem déploie des dispositifs connectés sur site en Nouvelle-Aquitaine. Basé à Poitiers, intervention physique chez le client pour l\'installation et la configuration.',
            ],
            'positioning' => VitrineContent::positioning(),
            'jsonLd' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Accueil',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Zone d\'intervention',
                            'item' => route('zone-intervention'),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function notes(): Response
    {
        return Inertia::render('Public/Notes', [
            'meta' => [
                'title' => 'Notes techniques — Kodem',
                'description' => 'Notes de fond sur les dispositifs connectés sur site : choix techniques, retours d\'expérience et compromis documentés par Kodem.',
            ],
            'jsonLd' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Accueil',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Notes techniques',
                            'item' => route('notes'),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
