<?php

namespace App\Services;

class AuditRecommendations
{
    /**
     * Renvoie la recommandation associée à un contrôle donné, ou null si le
     * contrôle n'a pas de recommandation (par exemple, il est déjà en `pass`).
     *
     * @return array{fix:string, snippet:?string, snippet_lang:?string, reference:?string, effort:string}|null
     */
    public static function for(string $key): ?array
    {
        return self::catalog()[$key] ?? null;
    }

    /**
     * @return array<string, array{fix:string, snippet:?string, snippet_lang:?string, reference:?string, effort:string}>
     */
    public static function catalog(): array
    {
        return [
            // ————————————————————————————————————— SEO
            'http_200' => [
                'fix' => 'Le serveur doit répondre avec un code 200 sur l\'URL auditée. Vérifiez les redirections (301/302), les erreurs 5xx et les murs d\'authentification qui empêchent l\'indexation.',
                'snippet' => null,
                'snippet_lang' => null,
                'reference' => 'https://developer.mozilla.org/fr/docs/Web/HTTP/Status',
                'effort' => 'medium',
            ],
            'title' => [
                'fix' => 'Ajoutez (ou raccourcissez / allongez) une balise <title> entre 15 et 65 caractères, avec le mot-clé principal au début.',
                'snippet' => "<title>Mot-clé principal — marque (nom du site)</title>",
                'snippet_lang' => 'html',
                'reference' => 'https://developers.google.com/search/docs/appearance/title-link',
                'effort' => 'low',
            ],
            'meta_description' => [
                'fix' => 'Ajoutez une meta description de 70 à 170 caractères qui résume la page avec un appel à l\'action.',
                'snippet' => '<meta name="description" content="Description claire et engageante de la page, entre 120 et 160 caractères idéalement.">',
                'snippet_lang' => 'html',
                'reference' => 'https://developers.google.com/search/docs/appearance/snippet',
                'effort' => 'low',
            ],
            'h1' => [
                'fix' => 'Ajoutez un et un seul <h1> par page, reflétant la thématique principale.',
                'snippet' => '<h1>Titre principal de la page</h1>',
                'snippet_lang' => 'html',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTML/Element/Heading_Elements',
                'effort' => 'low',
            ],
            'viewport' => [
                'fix' => 'Ajoutez la balise viewport pour le rendu mobile.',
                'snippet' => '<meta name="viewport" content="width=device-width, initial-scale=1">',
                'snippet_lang' => 'html',
                'reference' => 'https://web.dev/viewport/',
                'effort' => 'low',
            ],
            'html_lang' => [
                'fix' => 'Déclarez la langue principale du document sur la balise <html>.',
                'snippet' => '<html lang="fr">',
                'snippet_lang' => 'html',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTML/Global_attributes/lang',
                'effort' => 'low',
            ],
            'canonical' => [
                'fix' => 'Ajoutez une balise canonical pour éviter le contenu dupliqué.',
                'snippet' => '<link rel="canonical" href="https://www.votresite.fr/page">',
                'snippet_lang' => 'html',
                'reference' => 'https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls',
                'effort' => 'low',
            ],
            'og' => [
                'fix' => 'Ajoutez les balises Open Graph pour un meilleur rendu sur les réseaux sociaux.',
                'snippet' => <<<'HTML'
<meta property="og:title" content="Titre de la page">
<meta property="og:description" content="Description courte">
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.votresite.fr/page">
<meta property="og:image" content="https://www.votresite.fr/og.jpg">
HTML,
                'snippet_lang' => 'html',
                'reference' => 'https://ogp.me/',
                'effort' => 'low',
            ],
            'twitter' => [
                'fix' => 'Ajoutez une Twitter Card pour un affichage riche sur X/Twitter.',
                'snippet' => <<<'HTML'
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Titre">
<meta name="twitter:description" content="Description">
<meta name="twitter:image" content="https://www.votresite.fr/og.jpg">
HTML,
                'snippet_lang' => 'html',
                'reference' => 'https://developer.x.com/en/docs/x-for-websites/cards/overview/abouts-cards',
                'effort' => 'low',
            ],
            'compressed' => [
                'fix' => 'Activez la compression HTTP (gzip ou Brotli) côté serveur pour réduire le poids des pages.',
                'snippet' => "# nginx\ngzip on;\ngzip_types text/plain text/css application/javascript application/json image/svg+xml;\nbrotli on;\nbrotli_types text/plain text/css application/javascript application/json image/svg+xml;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://web.dev/articles/reduce-network-payloads-using-text-compression',
                'effort' => 'low',
            ],

            // ————————————————————————————————————— Sécurité
            'https' => [
                'fix' => 'Migrez le site en HTTPS. Un certificat Let\'s Encrypt est gratuit et automatisable.',
                'snippet' => "# Émettre un certificat avec Certbot (nginx) :\nsudo certbot --nginx -d votresite.fr -d www.votresite.fr",
                'snippet_lang' => 'bash',
                'reference' => 'https://letsencrypt.org/fr/getting-started/',
                'effort' => 'medium',
            ],
            'hsts' => [
                'fix' => 'Ajoutez l\'en-tête HSTS pour forcer HTTPS sur 2 ans, sous-domaines inclus, avec préchargement.',
                'snippet' => "# nginx\nadd_header Strict-Transport-Security \"max-age=63072000; includeSubDomains; preload\" always;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/Headers/Strict-Transport-Security',
                'effort' => 'low',
            ],
            'csp' => [
                'fix' => 'Ajoutez un Content-Security-Policy strict pour limiter les sources de scripts et contrer les XSS.',
                'snippet' => "# nginx — CSP stricte minimum\nadd_header Content-Security-Policy \"default-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests\" always;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/CSP',
                'effort' => 'medium',
            ],
            'x_frame' => [
                'fix' => 'Ajoutez X-Frame-Options: DENY (ou utilisez frame-ancestors \'none\' dans la CSP) pour bloquer le clickjacking.',
                'snippet' => "add_header X-Frame-Options \"DENY\" always;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/Headers/X-Frame-Options',
                'effort' => 'low',
            ],
            'x_content_type' => [
                'fix' => 'Ajoutez X-Content-Type-Options: nosniff pour empêcher le MIME-sniffing.',
                'snippet' => "add_header X-Content-Type-Options \"nosniff\" always;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/Headers/X-Content-Type-Options',
                'effort' => 'low',
            ],
            'referrer_policy' => [
                'fix' => 'Ajoutez un Referrer-Policy qui protège la vie privée des utilisateurs.',
                'snippet' => "add_header Referrer-Policy \"strict-origin-when-cross-origin\" always;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/Headers/Referrer-Policy',
                'effort' => 'low',
            ],
            'permissions_policy' => [
                'fix' => 'Ajoutez un Permissions-Policy qui désactive les fonctions sensibles inutiles (caméra, micro, géoloc).',
                'snippet' => "add_header Permissions-Policy \"camera=(), microphone=(), geolocation=(), interest-cohort=()\" always;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/Headers/Permissions-Policy',
                'effort' => 'low',
            ],
            'server_header' => [
                'fix' => 'Masquez la version du serveur Web dans l\'en-tête Server (nginx : server_tokens off — Apache : ServerTokens Prod).',
                'snippet' => "# nginx (http{})\nserver_tokens off;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://owasp.org/www-project-secure-headers/',
                'effort' => 'low',
            ],
            'x_powered_by' => [
                'fix' => 'Supprimez l\'en-tête X-Powered-By qui expose inutilement votre stack.',
                'snippet' => "# PHP — php.ini\nexpose_php = Off\n\n# nginx — si ajouté par un proxy\nproxy_hide_header X-Powered-By;",
                'snippet_lang' => 'nginx',
                'reference' => 'https://owasp.org/www-project-secure-headers/',
                'effort' => 'low',
            ],
            'cookies_secure' => [
                'fix' => 'Ajoutez les flags Secure, HttpOnly et SameSite à tous les cookies applicatifs.',
                'snippet' => "Set-Cookie: session=abc; Secure; HttpOnly; SameSite=Lax; Path=/",
                'snippet_lang' => 'http',
                'reference' => 'https://developer.mozilla.org/docs/Web/HTTP/Cookies',
                'effort' => 'low',
            ],
        ];
    }
}
