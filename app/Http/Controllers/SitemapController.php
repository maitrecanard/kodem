<?php

namespace App\Http\Controllers;

use App\Services\VitrineContent;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        // Prestations, hébergement, audits et monitoring sont hors sitemap depuis le vote
        // du 2026-09-29 : routes conservées, mais plus mises en avant.
        $urls = [
            ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => url('/contact'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => url('/mentions-legales'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => url('/cgv'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => url('/realisations'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => url('/expertises'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => url('/zone-intervention'), 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        foreach (VitrineContent::listedCases() as $cas) {
            $urls[] = ['loc' => url('/realisations/'.$cas['slug']), 'changefreq' => 'monthly', 'priority' => '0.7'];
        }

        $lastmod = now()->toDateString();

        return response()
            ->view('sitemap', compact('urls', 'lastmod'))
            ->header('Content-Type', 'application/xml');
    }
}
