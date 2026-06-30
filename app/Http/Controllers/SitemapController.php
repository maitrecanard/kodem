<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => url('/prestations'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => url('/hebergement-web'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => url('/audit'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => url('/monitoring'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => url('/contact'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => url('/mentions-legales'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => url('/cgv'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        $lastmod = now()->toDateString();

        return response()
            ->view('sitemap', compact('urls', 'lastmod'))
            ->header('Content-Type', 'application/xml');
    }
}
