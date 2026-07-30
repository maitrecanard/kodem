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
                'title' => 'Dispositifs connectés sur site — conçus, déployés, sécurisés | Kodem',
                'description' => 'Kodem développe le logiciel qui pilote vos bornes, écrans et installations interactives. Déploiement sur site partout en France, supervision dans la durée.',
            ],
            'positioning' => VitrineContent::positioning(),
            'cases' => VitrineContent::cases(),
            'testimonials' => VitrineContent::testimonials(),
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
                'title' => 'Contact — Kodem | Dispositifs connectés sur site en France',
                'description' => 'Concevoir ou déployer un dispositif connecté sur site : borne, écran piloté, installation interactive. Kodem intervient partout en France, réponse sous 48 h.',
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
                'description' => 'Développement embarqué, hébergement, SEO local et sécurité : les capacités qui soutiennent les dispositifs connectés déployés par Kodem partout en France.',
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
                'title' => 'Zone d\'intervention — Poitiers et partout en France | Kodem',
                'description' => 'Kodem déploie des dispositifs connectés sur site partout en France. Basé à Poitiers : intervention le jour même en Nouvelle-Aquitaine, sous 48 h ailleurs.',
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
            'notes' => VitrineContent::notes(),
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

    public function noteShow(string $slug): Response
    {
        $note = VitrineContent::note($slug);
        abort_unless($note, 404);

        return Inertia::render('Public/NoteShow', [
            'meta' => [
                'title' => $note['titre'].' — Notes techniques Kodem',
                'description' => $note['resume'],
            ],
            'note' => $note,
            'jsonLd' => [
                [
                    '@type' => 'TechArticle',
                    'headline' => $note['titre'],
                    'description' => $note['resume'],
                    'author' => ['@type' => 'Organization', 'name' => config('app.name', 'Kodem')],
                    'publisher' => ['@type' => 'Organization', 'name' => config('app.name', 'Kodem')],
                    'mainEntityOfPage' => route('notes.show', $slug),
                    'inLanguage' => 'fr-FR',
                ] + (isset($note['date_iso']) ? ['datePublished' => $note['date_iso']] : []),
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
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $note['titre'],
                            'item' => route('notes.show', $slug),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
