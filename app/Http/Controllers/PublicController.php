<?php

namespace App\Http\Controllers;

use App\Services\PrestationCatalog;
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
}
