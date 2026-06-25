<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

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
            $structuredData = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Organization',
                        '@id' => $appUrl.'/#organization',
                        'name' => $appName,
                        'url' => $appUrl,
                        'logo' => $appUrl.'/favicon.svg',
                        'image' => $appUrl.'/og-image.png',
                        'description' => 'Société de développement web, création de SaaS, hébergement web et audits SEO / sécurité automatisés.',
                        'email' => 'contact@kodem.fr',
                        'areaServed' => 'FR',
                    ],
                    [
                        '@type' => 'WebSite',
                        '@id' => $appUrl.'/#website',
                        'name' => $appName,
                        'url' => $appUrl,
                        'inLanguage' => 'fr-FR',
                        'publisher' => ['@id' => $appUrl.'/#organization'],
                    ],
                ],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

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
