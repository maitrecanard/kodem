<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        {{-- PAS de <title> ici. Le titre est rendu côté serveur par @inertiaHead (voir plus bas),
             à partir du <Head> de la page. Un <title inertia> en dur à cet endroit produisait un
             SECOND <title> dans le HTML brut, placé AVANT celui du SSR : la spec HTML n'en admet
             qu'un et c'est le premier qui fait foi, donc les crawlers lisaient « Kodem » sur
             toutes les pages malgré le SSR. Ne pas le réintroduire. --}}

        <!-- Favicon — symbole [k] de la marque KODEM -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <!-- Fonts — Charte KODEM : Space Grotesk (titrage & texte) + JetBrains Mono (libellés, code, données) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,700|space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Données structurées JSON-LD (Organization + WebSite) — rendues côté serveur --}}
        @php
            $appName = config('app.name', 'Kodem');
            $appUrl = rtrim(config('app.url', ''), '/');
            // Zone desservie : la France dans son ensemble. L'adresse et la geo ci-dessous restent
            // celles de la base physique (Poitiers) — siège, pas périmètre commercial.
            $areaServedFrance = ['@type' => 'Country', 'name' => 'France'];
            $graph = [
                [
                    '@type' => 'Organization',
                    '@id' => $appUrl.'/#organization',
                    'name' => $appName,
                    'url' => $appUrl,
                    'logo' => $appUrl.'/favicon.svg',
                    'image' => $appUrl.'/og-image.png',
                    'description' => 'Société de développement web, création de SaaS, hébergement web et audits SEO / sécurité automatisés.',
                    'email' => 'contact@kodem.fr',
                    'areaServed' => $areaServedFrance,
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $appUrl.'/#website',
                    'name' => $appName,
                    'url' => $appUrl,
                    'inLanguage' => 'fr-FR',
                    'publisher' => ['@id' => $appUrl.'/#organization'],
                ],
                [
                    '@type' => 'LocalBusiness',
                    '@id' => $appUrl.'/#localbusiness',
                    'name' => $appName,
                    'url' => $appUrl,
                    'image' => $appUrl.'/og-image.png',
                    // Base physique : ville + région du siège. La zone desservie est nationale (areaServed).
                    'address' => [
                        '@type' => 'PostalAddress',
                        'postalCode' => '86000',
                        'addressLocality' => 'Poitiers',
                        'addressRegion' => 'Nouvelle-Aquitaine',
                        'addressCountry' => 'FR',
                    ],
                    // Coordonnées au niveau ville (centre de Poitiers), pas le domicile.
                    'geo' => [
                        '@type' => 'GeoCoordinates',
                        'latitude' => '46.5802',
                        'longitude' => '0.3404',
                    ],
                    'telephone' => '+33 7 62 61 26 46',
                    'areaServed' => $areaServedFrance,
                ],
            ];
            foreach (\App\Services\PrestationCatalog::all() as $p) {
                $node = [
                    '@type' => 'Service',
                    '@id' => $appUrl.'/#service-'.$p['slug'],
                    'name' => $p['title'],
                    'description' => $p['description'],
                    'serviceType' => $p['title'],
                    'provider' => ['@id' => $appUrl.'/#organization'],
                    'areaServed' => $areaServedFrance,
                ];
                if (isset($p['price_from']) && is_numeric($p['price_from'])) {
                    $node['offers'] = [
                        '@type' => 'Offer',
                        'price' => (string) $p['price_from'],
                        'priceCurrency' => 'EUR',
                        'url' => $appUrl,
                    ];
                }
                $graph[] = $node;
            }
            foreach (\Illuminate\Support\Arr::wrap(data_get($page, 'props.jsonLd', [])) as $node) {
                $graph[] = $node;
            }
            $structuredData = [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
