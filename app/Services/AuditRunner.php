<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AuditRunner
{
    protected const MAX_REDIRECTS = 5;

    protected const USER_AGENT = 'KodemAuditBot/1.0 (+https://kodem.fr)';

    /**
     * Exécute un audit complet (SEO + sécurité) sur une URL.
     *
     * @return array{
     *     status: string,
     *     score_seo: int|null,
     *     score_security: int|null,
     *     score_total: int|null,
     *     results: array<string,mixed>,
     *     error: string|null
     * }
     */
    public function run(string $url): array
    {
        $normalized = $this->normalizeUrl($url);
        if ($normalized === null) {
            return $this->failure('URL invalide.');
        }

        $fetched = $this->fetch($normalized);
        if (is_string($fetched)) {
            return $this->failure($fetched);
        }

        $response = $fetched['response'];
        $finalUrl = $fetched['url'];

        $headers = $this->normalizeHeaders($response->headers());
        $body = (string) $response->body();

        $companions = $this->probeCompanions($finalUrl);

        $seo = $this->analyseSeo($finalUrl, $body, $headers, $response->status(), $companions);
        $sec = $this->analyseSecurity($finalUrl, $headers, $response->status());

        $seo['checks'] = $this->attachRecommendations($seo['checks']);
        $sec['checks'] = $this->attachRecommendations($sec['checks']);

        $scoreSeo = $this->score($seo['checks']);
        $scoreSec = $this->score($sec['checks']);
        $scoreTotal = (int) round(($scoreSeo + $scoreSec) / 2);

        $actionPlan = $this->buildActionPlan($seo['checks'], $sec['checks'], $scoreSeo, $scoreSec);

        return [
            'status' => 'completed',
            'score_seo' => $scoreSeo,
            'score_security' => $scoreSec,
            'score_total' => $scoreTotal,
            'results' => [
                'url' => $finalUrl,
                'requested_url' => $normalized,
                'http_status' => $response->status(),
                'response_time_ms' => $fetched['elapsed_ms'],
                'seo' => $seo,
                'security' => $sec,
                'companions' => $companions,
                'action_plan' => $actionPlan,
                'audited_at' => now()->toIso8601String(),
            ],
            'error' => null,
        ];
    }

    /**
     * Récupère l'URL en suivant les redirections MANUELLEMENT.
     *
     * Le client HTTP suivrait les redirections tout seul, mais sans repasser par
     * le garde-fou : une URL publique pourrait alors rediriger vers une adresse
     * interne et contourner le contrôle SSRF. Chaque saut est donc revalidé par
     * normalizeUrl(). Mesure aussi le temps réel de la requête.
     *
     * @return array{response: \Illuminate\Http\Client\Response, url: string, elapsed_ms: int}|string
     *         Une chaîne signale l'échec et porte le message destiné à l'utilisateur.
     */
    protected function fetch(string $url): array|string
    {
        $current = $url;
        $start = microtime(true);

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            try {
                $response = Http::timeout(15)
                    ->connectTimeout(10)
                    ->withUserAgent(self::USER_AGENT)
                    ->withHeaders(['Accept' => 'text/html,*/*'])
                    ->withOptions(['allow_redirects' => false])
                    ->get($current);
            } catch (\Throwable $e) {
                return 'Le site est injoignable : '.$e->getMessage();
            }

            $status = $response->status();

            if ($status < 300 || $status >= 400) {
                return [
                    'response' => $response,
                    'url' => $current,
                    'elapsed_ms' => (int) round((microtime(true) - $start) * 1000),
                ];
            }

            $location = trim((string) $response->header('Location'));
            if ($location === '') {
                return 'Redirection HTTP '.$status.' sans en-tête Location.';
            }

            $next = $this->absolutize($current, $location);
            $validated = $next === null ? null : $this->normalizeUrl($next);

            if ($validated === null) {
                return 'Redirection vers une URL non autorisée ou interne : '.$location;
            }

            $current = $validated;
        }

        return 'Trop de redirections (plus de '.self::MAX_REDIRECTS.').';
    }

    /**
     * Résout un en-tête Location relatif en URL absolue.
     */
    protected function absolutize(string $base, string $location): ?string
    {
        if (preg_match('~^https?://~i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        // Protocole-relatif : //exemple.fr/page
        if (str_starts_with($location, '//')) {
            return $parts['scheme'].':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $origin.$location;
        }

        $path = $parts['path'] ?? '/';
        $dir = rtrim(substr($path, 0, (int) strrpos($path, '/') + 1), '/');

        return $origin.$dir.'/'.$location;
    }

    /**
     * Ajoute la recommandation à chaque contrôle non-pass.
     *
     * @param array<int, array<string,mixed>> $checks
     * @return array<int, array<string,mixed>>
     */
    protected function attachRecommendations(array $checks): array
    {
        foreach ($checks as $i => $c) {
            if (($c['status'] ?? '') === 'pass') {
                continue;
            }
            $rec = AuditRecommendations::for($c['key'] ?? '');
            if ($rec) {
                $checks[$i]['recommendation'] = $rec;
            }
        }
        return $checks;
    }

    /**
     * Construit un plan d'action trié par impact : fail > warn,
     * puis par poids (gain potentiel) décroissant.
     *
     * @param array<int, array<string,mixed>> $seoChecks
     * @param array<int, array<string,mixed>> $secChecks
     * @return array{items: array<int, array<string,mixed>>, potential_gain_seo: int, potential_gain_security: int, potential_gain_total: int}
     */
    protected function buildActionPlan(array $seoChecks, array $secChecks, int $scoreSeo, int $scoreSec): array
    {
        $items = [];

        foreach ([['seo', $seoChecks], ['security', $secChecks]] as [$category, $checks]) {
            $totalWeight = array_sum(array_map(fn ($c) => $c['weight'] ?? 1, $checks)) ?: 1;

            foreach ($checks as $c) {
                $status = $c['status'] ?? 'fail';
                if ($status === 'pass') {
                    continue;
                }
                $weight = $c['weight'] ?? 1;
                // Gain de points si on passe de l'état courant à pass.
                $current = $status === 'warn' ? $weight * 0.5 : 0;
                $gain = (($weight - $current) / $totalWeight) * 100;

                $items[] = [
                    'category' => $category,
                    'key' => $c['key'] ?? '',
                    'label' => $c['label'] ?? '',
                    'status' => $status,
                    'detail' => $c['detail'] ?? '',
                    'weight' => $weight,
                    'potential_gain' => (int) round($gain),
                    'recommendation' => $c['recommendation'] ?? null,
                ];
            }
        }

        usort($items, function (array $a, array $b) {
            // fail avant warn
            $sa = $a['status'] === 'fail' ? 0 : 1;
            $sb = $b['status'] === 'fail' ? 0 : 1;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }
            return $b['potential_gain'] <=> $a['potential_gain'];
        });

        return [
            'items' => $items,
            'potential_gain_seo' => max(0, 100 - $scoreSeo),
            'potential_gain_security' => max(0, 100 - $scoreSec),
            'potential_gain_total' => max(0, 100 - (int) round(($scoreSeo + $scoreSec) / 2)),
        ];
    }

    /**
     * @param array<int, array<string,mixed>> $checks
     */
    protected function score(array $checks): int
    {
        if ($checks === []) {
            return 0;
        }
        $totalWeight = 0;
        $earned = 0;
        foreach ($checks as $c) {
            $weight = $c['weight'] ?? 1;
            $totalWeight += $weight;
            if (($c['status'] ?? 'fail') === 'pass') {
                $earned += $weight;
            } elseif (($c['status'] ?? '') === 'warn') {
                $earned += $weight * 0.5;
            }
        }
        if ($totalWeight === 0) {
            return 0;
        }
        return (int) max(0, min(100, round(($earned / $totalWeight) * 100)));
    }

    /**
     * @param array<string,string> $headers
     * @return array{checks: array<int, array<string,mixed>>}
     */
    protected function analyseSeo(string $url, string $body, array $headers, int $status, array $companions = []): array
    {
        $dom = $this->parseHtml($body);

        $title = $this->firstText($dom, '//title');
        $h1 = $this->firstText($dom, '//h1');
        $lang = $this->firstAttr($dom, '//html/@lang');
        $metas = $this->metaTags($dom);

        $metaDesc = $metas['name']['description'] ?? null;
        $viewport = isset($metas['name']['viewport']);
        $canonical = $this->linkHref($dom, 'canonical');

        $og = [];
        foreach ($metas['property'] ?? [] as $key => $value) {
            if (str_starts_with($key, 'og:')) {
                $og[substr($key, 3)] = $value;
            }
        }

        $twitter = [];
        foreach ($metas['name'] ?? [] as $key => $value) {
            if (str_starts_with($key, 'twitter:')) {
                $twitter[substr($key, 8)] = $value;
            }
        }

        $checks = [
            [
                'key' => 'http_200',
                'label' => 'Réponse HTTP 200 OK',
                'status' => $status >= 200 && $status < 300 ? 'pass' : 'fail',
                'weight' => 3,
                'detail' => "HTTP {$status}",
            ],
            [
                'key' => 'title',
                'label' => 'Balise <title> présente et de longueur raisonnable',
                'status' => $this->lenScore($title, 15, 65),
                'weight' => 3,
                'detail' => $title ? "Longueur : ".mb_strlen(trim($title))." car." : 'absente',
            ],
            [
                'key' => 'meta_description',
                'label' => 'Meta description présente et optimisée',
                'status' => $this->lenScore($metaDesc, 70, 170),
                'weight' => 3,
                'detail' => $metaDesc ? 'Longueur : '.mb_strlen($metaDesc).' car.' : 'absente',
            ],
            [
                'key' => 'h1',
                'label' => 'Balise <h1> présente',
                'status' => $h1 !== null ? 'pass' : 'fail',
                'weight' => 2,
                'detail' => $h1 !== null ? 'H1 détecté' : 'H1 manquant',
            ],
            [
                'key' => 'viewport',
                'label' => 'Meta viewport (mobile friendly)',
                'status' => $viewport ? 'pass' : 'fail',
                'weight' => 1,
                'detail' => $viewport ? 'présente' : 'absente',
            ],
            [
                'key' => 'html_lang',
                'label' => 'Attribut lang sur <html>',
                'status' => $lang ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $lang ?: 'non défini',
            ],
            [
                'key' => 'canonical',
                'label' => 'Balise canonical',
                'status' => $canonical ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $canonical ?: 'absente',
            ],
            [
                'key' => 'og',
                'label' => 'Open Graph (au moins og:title)',
                'status' => isset($og['title']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => isset($og['title']) ? 'og:title présent' : 'aucune balise og:*',
            ],
            [
                'key' => 'twitter',
                'label' => 'Twitter Card',
                'status' => isset($twitter['card']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => isset($twitter['card']) ? 'twitter:card présent' : 'absent',
            ],
            [
                'key' => 'compressed',
                'label' => 'Compression HTTP (gzip/br)',
                'status' => isset($headers['content-encoding']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['content-encoding'] ?? 'aucune',
            ],
            [
                'key' => 'robots_txt',
                'label' => 'Fichier robots.txt accessible',
                'status' => ($companions['robots.txt']['found'] ?? false) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => ($companions['robots.txt']['found'] ?? false)
                    ? 'présent'
                    : 'introuvable (HTTP '.($companions['robots.txt']['status'] ?? 0).')',
            ],
            [
                'key' => 'sitemap_xml',
                'label' => 'Sitemap XML accessible',
                'status' => ($companions['sitemap.xml']['found'] ?? false) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => ($companions['sitemap.xml']['found'] ?? false)
                    ? 'présent'
                    : 'introuvable (HTTP '.($companions['sitemap.xml']['status'] ?? 0).')',
            ],
        ];

        return [
            'summary' => [
                // Le DOM renvoie déjà du texte : plus de strip_tags à faire ici.
                'title' => $title,
                'description' => $metaDesc,
                'h1' => $h1,
                'lang' => $lang,
                'canonical' => $canonical,
            ],
            'checks' => $checks,
        ];
    }

    /**
     * @param array<string,string> $headers
     * @return array{checks: array<int, array<string,mixed>>}
     */
    protected function analyseSecurity(string $url, array $headers, int $status): array
    {
        $https = str_starts_with($url, 'https://');

        $setCookie = $headers['set-cookie'] ?? '';
        $cookieStatus = 'warn';
        if ($setCookie === '') {
            $cookieStatus = 'pass';
            $cookieDetail = 'aucun cookie';
        } else {
            $cookieLc = strtolower($setCookie);
            if (str_contains($cookieLc, 'secure') && str_contains($cookieLc, 'httponly')) {
                $cookieStatus = 'pass';
                $cookieDetail = 'Secure + HttpOnly détectés';
            } else {
                $cookieDetail = 'flags Secure/HttpOnly manquants';
            }
        }

        $checks = [
            [
                'key' => 'https',
                'label' => 'Site servi en HTTPS',
                'status' => $https ? 'pass' : 'fail',
                'weight' => 4,
                'detail' => $https ? 'HTTPS' : 'HTTP seulement — critique',
            ],
            [
                'key' => 'hsts',
                'label' => 'Strict-Transport-Security (HSTS)',
                'status' => isset($headers['strict-transport-security']) ? 'pass' : 'fail',
                'weight' => 3,
                'detail' => $headers['strict-transport-security'] ?? 'absent',
            ],
            [
                'key' => 'csp',
                'label' => 'Content-Security-Policy',
                'status' => isset($headers['content-security-policy']) ? 'pass' : 'fail',
                'weight' => 3,
                'detail' => isset($headers['content-security-policy']) ? 'présent' : 'absent',
            ],
            [
                'key' => 'x_frame',
                'label' => 'X-Frame-Options (clickjacking)',
                'status' => isset($headers['x-frame-options']) ? 'pass' : 'fail',
                'weight' => 2,
                'detail' => $headers['x-frame-options'] ?? 'absent',
            ],
            [
                'key' => 'x_content_type',
                'label' => 'X-Content-Type-Options: nosniff',
                'status' => (($headers['x-content-type-options'] ?? '') === 'nosniff') ? 'pass' : 'fail',
                'weight' => 2,
                'detail' => $headers['x-content-type-options'] ?? 'absent',
            ],
            [
                'key' => 'referrer_policy',
                'label' => 'Referrer-Policy',
                'status' => isset($headers['referrer-policy']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['referrer-policy'] ?? 'absent',
            ],
            [
                'key' => 'permissions_policy',
                'label' => 'Permissions-Policy',
                'status' => isset($headers['permissions-policy']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['permissions-policy'] ?? 'absent',
            ],
            [
                'key' => 'server_header',
                'label' => 'En-tête Server minimaliste',
                'status' => (! isset($headers['server']) || ! preg_match('/\d/', $headers['server'])) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['server'] ?? 'masqué',
            ],
            [
                'key' => 'x_powered_by',
                'label' => 'X-Powered-By absent',
                'status' => isset($headers['x-powered-by']) ? 'warn' : 'pass',
                'weight' => 1,
                'detail' => $headers['x-powered-by'] ?? 'absent (bon)',
            ],
            [
                'key' => 'cookies_secure',
                'label' => 'Cookies Secure + HttpOnly',
                'status' => $cookieStatus,
                'weight' => 2,
                'detail' => $cookieDetail,
            ],
        ];

        return ['checks' => $checks];
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    protected function probeCompanions(string $url): array
    {
        $base = preg_replace('~(https?://[^/]+).*~', '$1', $url);
        $result = [];
        foreach (['robots.txt', 'sitemap.xml'] as $file) {
            try {
                $res = Http::timeout(8)->withUserAgent('KodemAuditBot/1.0')->get($base.'/'.$file);
                $result[$file] = [
                    'found' => $res->successful(),
                    'status' => $res->status(),
                ];
            } catch (\Throwable $e) {
                $result[$file] = ['found' => false, 'status' => 0];
            }
        }
        return $result;
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (! isset($parts['host'])) {
            return null;
        }
        // Seuls http/https sont audités : file://, gopher://, dict:// etc. sont
        // des vecteurs SSRF classiques une fois passés à un client HTTP.
        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }
        $host = strtolower(trim($parts['host'], '[]'));
        if ($this->isBlockedHost($host)) {
            return null;
        }
        return $url;
    }

    /**
     * Garde-fou SSRF.
     *
     * L'URL est fournie par un visiteur anonyme : on doit garantir que le
     * serveur n'ira pas interroger une ressource interne pour son compte.
     * Le filtrage se fait sur les ADRESSES IP réellement résolues, pas sur la
     * chaîne du nom d'hôte — un domaine public peut pointer vers 127.0.0.1 ou
     * vers 169.254.169.254 (métadonnées d'instance cloud, qui exposent des
     * identifiants), et une IP peut s'écrire en décimal ou en hexadécimal.
     */
    protected function isBlockedHost(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return true;
        }
        // TLD réservés à un usage interne (RFC 6762 / 8375 / usage courant).
        foreach (['.localhost', '.local', '.internal', '.intranet', '.home.arpa'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! $this->isPublicIp($host);
        }

        $ips = $this->resolveHost($host);

        // Nom non résolu : on laisse le client HTTP échouer de lui-même plutôt
        // que de refuser un domaine valide dont la résolution a momentanément
        // échoué. Aucune requête interne n'est possible dans ce cas.
        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * FILTER_FLAG_NO_PRIV_RANGE couvre 10/8, 172.16/12, 192.168/16, fc00::/7 ;
     * FILTER_FLAG_NO_RES_RANGE couvre 0/8, 127/8, 169.254/16, 240/4, ::1, fe80::/10.
     */
    protected function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @return array<int, string> IPv4 et IPv6 résolues pour cet hôte
     */
    protected function resolveHost(string $host): array
    {
        $ips = [];

        $v4 = gethostbynamel($host); // false si non résolu, sans warning
        if (is_array($v4)) {
            $ips = $v4;
        }

        // checkdnsrr d'abord : dns_get_record émettrait un warning sur un
        // domaine sans enregistrement AAAA.
        if (checkdnsrr($host, 'AAAA')) {
            $records = dns_get_record($host, DNS_AAAA);
            foreach (is_array($records) ? $records : [] as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return $ips;
    }

    /**
     * @param array<string, array<int, string>|string> $raw
     * @return array<string,string>
     */
    protected function normalizeHeaders(array $raw): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $out[strtolower($k)] = is_array($v) ? implode(', ', $v) : (string) $v;
        }
        return $out;
    }

    /**
     * Analyse le HTML avec un vrai parseur DOM.
     *
     * Les expressions régulières utilisées auparavant imposaient un ordre
     * d'attributs (`name` avant `content`) et un type de guillemets : un
     * `<meta content="…" name="description">`, pourtant valide et courant,
     * était vu comme absent. Le DOM est indifférent à l'ordre, aux guillemets
     * et aux espaces.
     *
     * Retourne null si le corps est vide ou totalement illisible — les
     * contrôles retombent alors sur "absent", ce qui est le verdict correct.
     */
    protected function parseHtml(string $body): ?\DOMXPath
    {
        if (trim($body) === '') {
            return null;
        }

        // libxml signale des « erreurs » sur tout HTML5 valide (balises qu'il ne
        // connaît pas). On bascule sur son buffer interne le temps du parsing —
        // le succès reste vérifié par la valeur de retour de loadHTML().
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument;

        // LIBXML_NONET : interdit toute résolution d'entité externe par le
        // parseur (XXE / SSRF de second niveau sur un document hostile).
        $loaded = $doc->loadHTML(
            '<?xml encoding="utf-8" ?>'.$body,
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? new \DOMXPath($doc) : null;
    }

    protected function firstText(?\DOMXPath $dom, string $query): ?string
    {
        $node = $dom?->query($query)?->item(0);
        if ($node === null) {
            return null;
        }
        $text = trim($node->textContent);

        return $text === '' ? null : $text;
    }

    protected function firstAttr(?\DOMXPath $dom, string $query): ?string
    {
        $node = $dom?->query($query)?->item(0);
        $value = $node === null ? '' : trim($node->nodeValue ?? '');

        return $value === '' ? null : $value;
    }

    /**
     * Indexe toutes les balises meta par attribut porteur (name / property),
     * en minuscules, pour un accès insensible à la casse.
     *
     * @return array{name: array<string,string>, property: array<string,string>}
     */
    protected function metaTags(?\DOMXPath $dom): array
    {
        $out = ['name' => [], 'property' => []];
        if ($dom === null) {
            return $out;
        }

        foreach ($dom->query('//meta') ?: [] as $meta) {
            if (! $meta instanceof \DOMElement) {
                continue;
            }
            $content = trim($meta->getAttribute('content'));
            foreach (['name', 'property'] as $carrier) {
                $key = strtolower(trim($meta->getAttribute($carrier)));
                if ($key !== '' && ! isset($out[$carrier][$key])) {
                    $out[$carrier][$key] = $content;
                }
            }
        }

        return $out;
    }

    /**
     * `rel` peut porter plusieurs valeurs séparées par des espaces
     * (rel="alternate canonical") : on teste chaque jeton, pas la chaîne entière.
     */
    protected function linkHref(?\DOMXPath $dom, string $rel): ?string
    {
        if ($dom === null) {
            return null;
        }

        foreach ($dom->query('//link') ?: [] as $link) {
            if (! $link instanceof \DOMElement) {
                continue;
            }
            $tokens = preg_split('~\s+~', strtolower(trim($link->getAttribute('rel')))) ?: [];
            if (in_array($rel, $tokens, true)) {
                $href = trim($link->getAttribute('href'));
                if ($href !== '') {
                    return $href;
                }
            }
        }

        return null;
    }

    protected function lenScore(?string $s, int $min, int $max): string
    {
        if ($s === null) {
            return 'fail';
        }
        $len = mb_strlen(trim(strip_tags($s)));
        if ($len >= $min && $len <= $max) {
            return 'pass';
        }
        return $len === 0 ? 'fail' : 'warn';
    }

    /**
     * @return array{status:string, score_seo:null, score_security:null, score_total:null, results:array<string,mixed>, error:string}
     */
    protected function failure(string $msg): array
    {
        return [
            'status' => 'failed',
            'score_seo' => null,
            'score_security' => null,
            'score_total' => null,
            'results' => [],
            'error' => $msg,
        ];
    }
}
