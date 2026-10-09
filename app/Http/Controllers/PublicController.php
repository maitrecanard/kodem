<?php

namespace App\Http\Controllers;

use App\Services\PrestationCatalog;
use App\Services\VitrineContent;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    private const NOINDEX = 'noindex, follow';

    public function home(): Response
    {
        // Décision de l'actionnaire du 2026-10-09 : l'accueil reprend la maquette « backend de
        // votre produit ». Son contenu est porté par Home.jsx, la page ne reçoit que ses meta.
        return Inertia::render('Public/Home', [
            'meta' => [
                'title' => 'Backend Laravel et Symfony pour votre produit | Kodem',
                'description' => 'API, paiements, temps réel, tests et mise en production, en Laravel ou en Symfony : le backend de votre produit, de l\'architecture au déploiement. À distance, partout en France.',
            ],
        ]);
    }

    public function services(): Response
    {
        return Inertia::render('Public/Services', [
            'meta' => [
                'title' => 'Prestations — Création de site internet, logiciel, application web & hébergement | Kodem',
                'description' => 'Toutes les prestations Kodem : création de site internet, d\'application web et de logiciel sur-mesure, hébergement web managé, audit SEO et audit de sécurité automatisés.',
                'keywords' => 'création site internet, application web, création logiciel, hébergement web, audit SEO, audit de sécurité',
                // Ancien catalogue, hors navigation depuis le recentrage : la route reste servie mais n'est plus indexée.
                'robots' => self::NOINDEX,
            ],
            'prestations' => PrestationCatalog::all(),
        ]);
    }

    public function hebergement(): Response
    {
        $prestation = collect(PrestationCatalog::all())->firstWhere('slug', 'hebergement-web');

        return Inertia::render('Public/Hebergement', [
            'meta' => [
                'title' => 'Hébergement web managé | Kodem',
                'description' => 'Hébergement des applications conçues par KODEM : TLS automatique, sauvegardes chiffrées, supervision et correctifs.',
                'keywords' => 'hébergement web, hébergement web managé, hébergement sécurisé, hébergement application web',
                // Réservé aux clients existants, sans offre publique : hors navigation et hors index.
                'robots' => self::NOINDEX,
            ],
            'prestation' => $prestation,
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact', [
            'meta' => [
                'title' => 'Contact : décrivez la tâche à supprimer | Kodem',
                'description' => 'Décrivez la tâche manuelle que vous voulez supprimer et les logiciels concernés. Réponse sous 48 h, partout en France.',
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
        $positioning = VitrineContent::positioning();

        return Inertia::render('Public/Realisations', [
            'meta' => [
                'title' => 'Réalisations : outils métier en production | Kodem',
                'description' => 'MUXEN, outil de mises en service conçu de zéro, et Freendzy, application en production : problème, contrainte, choix, résultat.',
            ],
            'intro' => $positioning['realisations_intro'],
            'cases' => VitrineContent::listedCases(),
            'ctaFinal' => $positioning['cta_final'],
            'jsonLd' => [$this->buildBreadcrumbList(['Réalisations' => route('realisations.index')])],
        ]);
    }

    public function realisationShow(string $slug): Response
    {
        $cas = VitrineContent::case($slug);
        abort_unless($cas, 404);

        $meta = [
            'title' => $cas['titre'].' | Kodem',
            'description' => $cas['resume'],
        ];

        if (VitrineContent::isArchived($cas)) {
            $meta['robots'] = self::NOINDEX;
        }

        return Inertia::render('Public/RealisationShow', [
            'meta' => $meta,
            'cas' => $cas,
            'testimonials' => VitrineContent::testimonialsForCase($slug),
            'ctaFinal' => VitrineContent::positioning()['cta_final'],
            'jsonLd' => [$this->buildBreadcrumbList([
                'Réalisations' => route('realisations.index'),
                $cas['titre'] => route('realisations.show', $slug),
            ])],
        ]);
    }

    public function expertises(): Response
    {
        $positioning = VitrineContent::positioning();

        return Inertia::render('Public/Expertises', [
            'meta' => [
                'title' => 'Expertises : automatiser les tâches manuelles | Kodem',
                'description' => 'Applications métier conçues de bout en bout, puis exploitées et maintenues : ce que KODEM automatise, de la conception à la production, partout en France.',
            ],
            'positioning' => $positioning,
            'jsonLd' => [
                $this->buildBreadcrumbList(['Expertises' => route('expertises')]),
                $this->buildFaqPage($positioning['faq']),
            ],
        ]);
    }

    public function zoneIntervention(): Response
    {
        return Inertia::render('Public/ZoneIntervention', [
            'meta' => [
                'title' => 'Méthode de travail, à distance depuis Poitiers | Kodem',
                'description' => 'Basé à Poitiers, KODEM conçoit et met en production vos outils à distance. Sur site : le jour même en Nouvelle-Aquitaine, sous 48 h ailleurs en France.',
            ],
            'ctaFinal' => VitrineContent::positioning()['cta_final'],
            'jsonLd' => [$this->buildBreadcrumbList(['Méthode de travail' => route('zone-intervention')])],
        ]);
    }

    public function notes(): Response
    {
        return Inertia::render('Public/Notes', [
            'meta' => [
                'title' => 'Notes techniques | Kodem',
                'description' => 'Notes de fond de KODEM : choix techniques, retours d\'expérience et compromis documentés sur des projets livrés.',
                'robots' => self::NOINDEX,
            ],
            'notes' => VitrineContent::notes(),
            'jsonLd' => [$this->buildBreadcrumbList(['Notes techniques' => route('notes')])],
        ]);
    }

    public function noteShow(string $slug): Response
    {
        $note = VitrineContent::note($slug);
        abort_unless($note, 404);

        return Inertia::render('Public/NoteShow', [
            'meta' => [
                'title' => $note['titre'].' | Notes techniques Kodem',
                'description' => $note['resume'],
                'robots' => self::NOINDEX,
            ],
            'note' => $note,
            'jsonLd' => [
                $this->buildTechArticle($note, $slug),
                $this->buildBreadcrumbList([
                    'Notes techniques' => route('notes'),
                    $note['titre'] => route('notes.show', $slug),
                ]),
            ],
        ]);
    }

    /**
     * @param  array<string,string>  $trail  libellé => URL absolue, après « Accueil »
     * @return array<string,mixed>
     */
    private function buildBreadcrumbList(array $trail): array
    {
        $crumbs = ['Accueil' => url('/')] + $trail;
        $items = [];

        foreach ($crumbs as $name => $url) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => (string) $name,
                'item' => $url,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @param  array{titre: string, resume: string, date_iso?: string}  $note
     * @return array<string,mixed>
     */
    private function buildTechArticle(array $note, string $slug): array
    {
        $publisher = ['@type' => 'Organization', 'name' => config('app.name', 'Kodem')];

        return [
            '@type' => 'TechArticle',
            'headline' => $note['titre'],
            'description' => $note['resume'],
            'author' => $publisher,
            'publisher' => $publisher,
            'mainEntityOfPage' => route('notes.show', $slug),
            'inLanguage' => 'fr-FR',
        ] + (isset($note['date_iso']) ? ['datePublished' => $note['date_iso']] : []);
    }

    /**
     * @param  list<array{question: string, reponse: string}>  $faq
     * @return array<string,mixed>
     */
    private function buildFaqPage(array $faq): array
    {
        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['reponse'],
                ],
            ], $faq),
        ];
    }
}
