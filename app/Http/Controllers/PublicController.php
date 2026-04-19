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
                'title' => 'Kodem — Développement web, hébergement web, audit SEO et audit de sécurité',
                'description' => 'Kodem, société de développement web, création de SaaS, hébergement web et audit SEO / audit de sécurité automatisé. Lancez votre audit en ligne gratuitement.',
                'keywords' => 'audit SEO, audit de sécurité, développement web, hébergement web, création de saas',
            ],
            'prestations' => PrestationCatalog::teaser(),
        ]);
    }

    public function services(): Response
    {
        return Inertia::render('Public/Services', [
            'meta' => [
                'title' => 'Prestations — Développement web, création SaaS, hébergement et audits | Kodem',
                'description' => 'Toutes les prestations Kodem : développement web sur-mesure, création de SaaS, hébergement web managé, audit SEO et audit de sécurité automatisés.',
                'keywords' => 'développement web, création de saas, hébergement web, audit SEO, audit de sécurité',
            ],
            'prestations' => PrestationCatalog::all(),
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
