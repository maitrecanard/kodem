<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Garde-fou du budget de mouvement KODEM.
 *
 * Ce lot pose des PRIMITIVES d'animation (tokens CSS, keyframes Tailwind,
 * patterns composés) qui sont désormais consommées par 23 fichiers de la
 * surface publique (pages Public/Audits, PublicLayout, composants de rendu
 * public) : le diff visuel du site n'est donc plus nul. Ce test protège les
 * propriétés structurelles de ces primitives — accessibilité (WCAG 2.1 AA
 * § 2.3.3), performance (paint-only, jamais de layout), protection du LCP, et
 * absence de dépendance externe — pour que toute consommation, présente et
 * future, respecte le même contrat.
 *
 * Surface publique scannée : pages Public/Audits, PublicLayout et les
 * composants de rendu public listés dans PUBLIC_SURFACE_COMPONENTS.
 * Explicitement EXCLUS (jamais globés ici) : Modal.jsx, Dropdown.jsx,
 * Pages/Admin/**, Pages/Profile/**, AdminLayout.jsx — `transition-all` y
 * existe déjà légitimement.
 */
class MotionGuardTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const PUBLIC_SURFACE_COMPONENTS = [
        'Banner',
        'SectionLabel',
        'CodeButton',
        'DiagonalPattern',
        'ContactBlockMono',
        'BrandWordmark',
        'CaseStudy',
        'Testimonial',
        'CaseImage',
    ];

    /**
     * @return list<string>
     */
    private function publicSurfaceFiles(): array
    {
        $files = $this->globRecursive(base_path('resources/js/Pages/Public'), '*.jsx');
        $files = array_merge($files, $this->globRecursive(base_path('resources/js/Pages/Audits'), '*.jsx'));
        $files[] = base_path('resources/js/Layouts/PublicLayout.jsx');

        foreach (self::PUBLIC_SURFACE_COMPONENTS as $component) {
            $files[] = base_path("resources/js/Components/{$component}.jsx");
        }

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    private function globRecursive(string $dir, string $pattern): array
    {
        $found = glob($dir.'/'.$pattern) ?: [];

        foreach (glob($dir.'/*', GLOB_ONLYDIR) ?: [] as $subDir) {
            // Ne jamais suivre un lien symbolique : évite toute récursion infinie.
            if (is_link($subDir)) {
                continue;
            }

            $found = array_merge($found, $this->globRecursive($subDir, $pattern));
        }

        return $found;
    }

    private function readRaw(string $path): string
    {
        $this->assertTrue(is_readable($path), "le fichier attendu est illisible : {$path}");

        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "la lecture du fichier a échoué : {$path}");

        return $contents;
    }

    /**
     * Extrait le premier bloc `{ ... }` équilibré (accolades comptées) dont
     * l'accolade ouvrante se trouve à $openBracePos. Travaille en octets
     * (substr/strlen, jamais mb_*) pour rester cohérent avec les offsets
     * d'octets retournés par PREG_OFFSET_CAPTURE — les commentaires FR de ces
     * fichiers contiennent des caractères accentués multi-octets.
     */
    private function extractBalancedBlock(string $content, int $openBracePos): string
    {
        $depth = 0;
        $length = strlen($content);

        for ($i = $openBracePos; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $openBracePos, $i - $openBracePos + 1);
                }
            }
        }

        $this->fail('bloc non équilibré (accolades) dans le contenu scanné — extraction impossible');
    }

    /**
     * Tokenise le JSX en tags ouvrants/fermants, en restant robuste aux
     * expressions JS insérées dans les attributs (ex. `onClick={() => ...}`,
     * dont la flèche `=>` contient un `>` littéral qui ne doit PAS être
     * confondu avec la fin du tag). Heuristique volontairement conservatrice
     * — ce n'est pas un vrai parseur JSX/Babel — mais suffisante ici : elle
     * ne regarde jamais le texte hors tag (un `<` isolé suivi d'un espace,
     * ex. `{a < b}`, ne matche pas `[A-Za-z]` et est donc ignoré sans casser
     * le comptage), et elle respecte les chaînes ("...", '...', `...`) et la
     * profondeur des accolades `{ }` pour localiser le VRAI `>` de fermeture
     * du tag, même s'il contient une flèche `=>` ou un opérateur `>`.
     * Prouvée par mutation (cf. consigne du lot) : voir le rapport
     * d'exécution pour le détail des cas rouges constatés.
     *
     * @return list<array{closing: bool, name: string, attrs: string, selfClosing: bool}>
     */
    private function scanJsxTags(string $content): array
    {
        $tags = [];
        $length = strlen($content);
        $i = 0;

        while ($i < $length) {
            if ($content[$i] !== '<') {
                $i++;

                continue;
            }

            if (! preg_match('/\G(\/?)([A-Za-z][\w.]*)/', $content, $m, 0, $i + 1)) {
                $i++;

                continue;
            }

            $closing = $m[1] === '/';
            $name = $m[2];
            $cursor = $i + 1 + strlen($m[0]);

            $braceDepth = 0;
            $quote = null;
            $selfClosing = false;
            $tagEnd = null;

            for ($j = $cursor; $j < $length; $j++) {
                $char = $content[$j];

                if ($quote !== null) {
                    if ($char === '\\') {
                        $j++;

                        continue;
                    }

                    if ($char === $quote) {
                        $quote = null;
                    }

                    continue;
                }

                if ($char === '"' || $char === "'" || $char === '`') {
                    $quote = $char;

                    continue;
                }

                if ($char === '{') {
                    $braceDepth++;

                    continue;
                }

                if ($char === '}') {
                    $braceDepth--;

                    continue;
                }

                if ($braceDepth > 0) {
                    continue;
                }

                if ($char === '>') {
                    $selfClosing = $j > $cursor && $content[$j - 1] === '/';
                    $tagEnd = $j;

                    break;
                }
            }

            if ($tagEnd === null) {
                $i++;

                continue;
            }

            $tags[] = [
                'closing' => $closing,
                'name' => $name,
                'attrs' => substr($content, $cursor, $tagEnd - $cursor),
                'selfClosing' => $selfClosing,
            ];

            $i = $tagEnd + 1;
        }

        return $tags;
    }

    /**
     * Détecte une classe EXACTE (`kodem-reveal` ou `kodem-reveal-grid`) dans un
     * attribut de tag brut, jamais par sous-chaîne : `kodem-reveal-grid`
     * CONTIENT littéralement `kodem-reveal`, donc un simple `str_contains`
     * confondrait les deux classes (et déclencherait ce garde à tort partout
     * où `kodem-reveal-grid` est posé sans jamais y avoir de vrai
     * `kodem-reveal`). Les frontières interdisent tout caractère de nom de
     * classe (lettre, chiffre, `_`, `-`) immédiatement avant ou après la
     * classe recherchée, ce qui exclut à la fois `kodem-reveal-grid` (quand on
     * cherche `kodem-reveal`, le `-` qui suit casse la frontière) et
     * `kodem-reveal-grid--2` (quand on cherche `kodem-reveal-grid`, le `-` de
     * `--2` casse la frontière).
     */
    private function attrsHasExactClass(string $attrs, string $className): bool
    {
        $pattern = '/(?<![\w-])'.preg_quote($className, '/').'(?![\w-])/';

        return (bool) preg_match($pattern, $attrs);
    }

    /**
     * @return array<string, string> nom du keyframe => bloc CSS complet
     */
    private function extractKeyframeBlocksFromCss(string $css): array
    {
        $blocks = [];

        if (preg_match_all('/@keyframes\s+([\w-]+)\s*\{/', $css, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $index => $nameMatch) {
                $name = $nameMatch[0];
                $fullMatch = $matches[0][$index][0];
                $matchOffset = $matches[0][$index][1];
                $openBracePos = $matchOffset + strlen($fullMatch) - 1;
                $blocks[$name] = $this->extractBalancedBlock($css, $openBracePos);
            }
        }

        return $blocks;
    }

    private function extractTopLevelObjectFromTailwindConfig(string $key): string
    {
        $js = $this->readRaw(base_path('tailwind.config.js'));
        $pattern = '/'.preg_quote($key, '/').'\s*:\s*\{/';

        $this->assertMatchesRegularExpression($pattern, $js, "précondition : tailwind.config.js doit déclarer \"{$key}\"");

        preg_match($pattern, $js, $match, PREG_OFFSET_CAPTURE);
        $openBracePos = $match[0][1] + strlen($match[0][0]) - 1;

        return $this->extractBalancedBlock($js, $openBracePos);
    }

    private function removeBalancedBlockFollowing(string $content, string $needle): string
    {
        $needlePos = strpos($content, $needle);
        $this->assertNotFalse($needlePos, "précondition : \"{$needle}\" doit être présent dans le CSS scanné");

        $openBrace = strpos($content, '{', $needlePos);
        $this->assertNotFalse($openBrace);

        $block = $this->extractBalancedBlock($content, $openBrace);
        $blockEnd = $openBrace + strlen($block);

        return substr($content, 0, $needlePos).substr($content, $blockEnd);
    }

    public function test_reduced_motion_guard_exists_in_app_css(): void
    {
        $css = $this->readRaw(base_path('resources/css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('animation-duration: 0.01ms !important', $css);
        $this->assertStringContainsString('transition-duration: 0.01ms !important', $css);
    }

    public function test_no_keyframe_animates_a_layout_property(): void
    {
        $layoutProperties = [
            'height', 'width', 'top', 'left', 'right', 'bottom', 'inset',
            'margin', 'padding', 'font-size', 'line-height', 'gap', 'flex-basis',
        ];

        // Ancré sur la POSITION de propriété (nom directement suivi d'un ':',
        // avec guillemet optionnel pour les clés JS) : `transform-origin: left
        // center` ne matche pas, "left" y est une VALEUR, pas une propriété.
        $pattern = '/(?<![\w-])('.implode('|', array_map(
            static fn (string $p) => preg_quote($p, '/'),
            $layoutProperties
        )).')[\'"]?\s*:/';

        $css = $this->readRaw(base_path('resources/css/app.css'));
        foreach ($this->extractKeyframeBlocksFromCss($css) as $name => $block) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $block,
                "le @keyframes CSS \"{$name}\" anime une propriété de layout"
            );
        }

        // Source de vérité principale des primitives KODEM : l'objet keyframes
        // de tailwind.config.js (le CSS source, lui, ne contient aujourd'hui
        // aucun @keyframes brut — ils sont générés par Tailwind à la build).
        $jsKeyframes = $this->extractTopLevelObjectFromTailwindConfig('keyframes');
        $this->assertDoesNotMatchRegularExpression(
            $pattern,
            $jsKeyframes,
            "l'objet keyframes de tailwind.config.js anime une propriété de layout"
        );
    }

    public function test_no_transition_all_on_public_surface(): void
    {
        $files = $this->publicSurfaceFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        foreach ($files as $file) {
            $contents = $this->readRaw($file);
            $this->assertStringNotContainsString('transition-all', $contents, "transition-all trouvé dans {$file}");
            $this->assertStringNotContainsString('transition-[', $contents, "transition-[ trouvé dans {$file}");
        }
    }

    /**
     * `animation-timeline` (animations pilotées par le scroll) est ABSENTE du
     * CSS aujourd'hui : ce test protège le futur. Si la propriété est
     * introduite un jour, elle doit obligatoirement être encapsulée dans un
     * `@supports (animation-timeline...)`, faute de quoi elle casse
     * silencieusement sur les moteurs qui ne la supportent pas encore.
     * Tant que la chaîne est absente, ce test passe trivialement — c'est
     * volontaire et documenté ici (cf. consigne du lot).
     */
    /**
     * Recherche le début du dernier `@supports (animation-timeline...)` dont
     * le DÉBUT se situe au plus à la position `$pos`. Un simple `strrpos` sur
     * le texte tronqué avant `$pos` échouerait pour l'occurrence
     * auto-référentielle de la condition `@supports (animation-timeline:
     * ...)` elle-même : le texte recherché (`@supports (animation-timeline`)
     * CONTIENT `animation-timeline` en son sein, et se termine donc quelques
     * octets APRÈS le `$pos` de cette occurrence-là — un garde est pourtant,
     * par définition, son propre garde, et doit être reconnu comme tel.
     */
    private function lastSupportsGuardStartAtOrBefore(string $css, string $needle, int $pos): int|false
    {
        $guardPos = false;
        $searchFrom = 0;

        while (($candidate = strpos($css, $needle, $searchFrom)) !== false && $candidate <= $pos) {
            $guardPos = $candidate;
            $searchFrom = $candidate + 1;
        }

        return $guardPos;
    }

    public function test_animation_timeline_is_always_supports_guarded(): void
    {
        $css = $this->readRaw(base_path('resources/css/app.css'));
        $occurrences = substr_count($css, 'animation-timeline');

        if ($occurrences === 0) {
            $this->assertSame(0, $occurrences);

            return;
        }

        $offset = 0;
        $guardedCount = 0;

        while (($pos = strpos($css, 'animation-timeline', $offset)) !== false) {
            $guardPos = $this->lastSupportsGuardStartAtOrBefore($css, '@supports (animation-timeline', $pos);
            $this->assertNotFalse(
                $guardPos,
                "occurrence de animation-timeline (octet {$pos}) hors de tout @supports (animation-timeline...)"
            );

            $openBrace = strpos($css, '{', $guardPos);
            $this->assertNotFalse($openBrace);

            $block = $this->extractBalancedBlock($css, $openBrace);
            $blockEnd = $openBrace + strlen($block);

            $this->assertLessThan(
                $blockEnd,
                $pos,
                "occurrence de animation-timeline (octet {$pos}) en dehors du bloc @supports ouvert en {$guardPos}"
            );

            $guardedCount++;
            $offset = $pos + strlen('animation-timeline');
        }

        $this->assertSame($occurrences, $guardedCount);
    }

    public function test_no_inline_animation_style_in_jsx(): void
    {
        $forbiddenKeys = [
            'animation', 'animationDelay', 'animationName', 'animationDuration',
            'transition', 'transitionDuration', 'transform', 'willChange',
        ];
        $pattern = '/(?<!\w)('.implode('|', $forbiddenKeys).')(?!\w)[\'"]?\s*:/';

        $files = $this->publicSurfaceFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        $foundAtLeastOneInlineStyle = false;

        foreach ($files as $file) {
            $contents = $this->readRaw($file);

            $offset = 0;
            while (($pos = strpos($contents, 'style={{', $offset)) !== false) {
                $foundAtLeastOneInlineStyle = true;

                $openBrace = $pos + strlen('style=');
                $block = $this->extractBalancedBlock($contents, $openBrace);

                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $block,
                    "style inline animé trouvé dans {$file} : {$block}"
                );

                $offset = $pos + strlen('style={{');
            }
        }

        $this->assertTrue(
            $foundAtLeastOneInlineStyle,
            'précondition : au moins un style={{ }} doit exister sur la surface (BrandWordmark, Premium)'
        );
    }

    public function test_no_infinite_animation(): void
    {
        $css = $this->readRaw(base_path('resources/css/app.css'));
        $this->assertStringNotContainsString('infinite', $css);

        $animationBlock = $this->extractTopLevelObjectFromTailwindConfig('animation');
        $this->assertStringNotContainsString('infinite', $animationBlock);
    }

    public function test_motion_values_come_from_tokens(): void
    {
        $animationBlock = $this->extractTopLevelObjectFromTailwindConfig('animation');

        // Chaque valeur de `theme.extend.animation` doit référencer le token
        // de durée ET le token de courbe — aucune valeur littérale glissée ici.
        preg_match_all("/:\s*'([^']*)'/", $animationBlock, $matches);
        $this->assertNotEmpty($matches[1], 'précondition : au moins une valeur d\'animation doit être scannée');

        foreach ($matches[1] as $value) {
            $this->assertStringContainsString('var(--kodem-dur-', $value, "valeur d'animation sans token de durée : {$value}");
            $this->assertStringContainsString('var(--kodem-ease', $value, "valeur d'animation sans token de courbe : {$value}");
        }

        // Aucune durée littérale (ex. "220ms", "0.4s") dans app.css en dehors
        // du bloc :root (où les tokens sont DÉFINIS) et du bloc
        // prefers-reduced-motion (où 0.01ms est un neutralisant volontaire).
        // Les commentaires /* ... */ sont retirés avant l'analyse : la règle
        // porte sur les déclarations CSS réelles, pas sur la prose qui
        // documente ces mêmes valeurs (ex. le commentaire qui explique le
        // choix de 0.01ms juste avant le bloc prefers-reduced-motion).
        $css = $this->readRaw(base_path('resources/css/app.css'));
        $body = $this->removeBalancedBlockFollowing($css, ':root');
        $body = $this->removeBalancedBlockFollowing($body, '@media (prefers-reduced-motion: reduce)');
        $body = preg_replace('#/\*.*?\*/#s', '', $body);
        $this->assertNotNull($body, 'précondition : le retrait des commentaires CSS a échoué');

        $this->assertDoesNotMatchRegularExpression(
            '/\d+m?s\b/',
            $body,
            'valeur de durée littérale trouvée dans app.css en dehors de :root et prefers-reduced-motion'
        );
    }

    public function test_no_will_change_on_public_surface(): void
    {
        $css = $this->readRaw(base_path('resources/css/app.css'));
        $this->assertStringNotContainsString('will-change', $css);

        foreach ($this->publicSurfaceFiles() as $file) {
            $contents = $this->readRaw($file);
            $this->assertStringNotContainsString('will-change', $contents, "will-change trouvé dans {$file}");
            $this->assertStringNotContainsString('willChange', $contents, "willChange trouvé dans {$file}");
        }
    }

    public function test_no_animation_library_installed(): void
    {
        $contents = $this->readRaw(base_path('package.json'));
        $decoded = json_decode($contents, true);
        $this->assertIsArray($decoded, 'package.json doit être un JSON valide');

        $declaredPackages = [];
        foreach (['dependencies', 'devDependencies'] as $section) {
            if (isset($decoded[$section]) && is_array($decoded[$section])) {
                $declaredPackages = array_merge($declaredPackages, array_keys($decoded[$section]));
            }
        }

        $this->assertNotEmpty($declaredPackages, 'précondition : package.json doit déclarer des dépendances');

        // Recherche par nom de paquet EXACT (clé du dépendances), jamais par
        // sous-chaîne dans le fichier brut : "motion" seul matcherait
        // "framer-motion" mais produirait des faux positifs sur d'autres
        // paquets légitimes (ex. futurs paquets contenant "motion").
        $forbiddenNeedles = ['framer-motion', 'gsap', 'aos', 'react-spring', 'animejs', 'lottie'];

        foreach ($declaredPackages as $package) {
            foreach ($forbiddenNeedles as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $package,
                    "dépendance d'animation interdite installée : {$package}"
                );
            }

            $this->assertFalse(
                str_starts_with($package, '@react-spring/'),
                "dépendance d'animation interdite installée : {$package}"
            );
        }
    }

    public function test_public_layout_chrome_is_never_animated(): void
    {
        $contents = $this->readRaw(base_path('resources/js/Layouts/PublicLayout.jsx'));

        $this->assertStringNotContainsString('animate-kodem', $contents);
        $this->assertStringNotContainsString('kodem-stagger', $contents);
    }

    /**
     * Contrôle ligne à ligne aujourd'hui trivialement dépassé si Prettier
     * reformate le `className` du `<h1>` ou du `<img>` sur plusieurs lignes
     * (le nom de la classe se retrouverait alors sur une ligne SANS `<h1`/
     * `<img`, et l'ancienne version de ce test devenait un faux-vert
     * silencieux). Utilise donc `scanJsxTags` pour lire l'intégralité des
     * attributs du tag, quel que soit son découpage en lignes.
     */
    public function test_banner_lcp_elements_are_not_animated(): void
    {
        $contents = $this->readRaw(base_path('resources/js/Components/Banner.jsx'));
        $tags = $this->scanJsxTags($contents);

        $targetTags = array_values(array_filter(
            $tags,
            static fn (array $tag): bool => ! $tag['closing'] && in_array($tag['name'], ['h1', 'img'], true)
        ));

        $this->assertNotEmpty($targetTags, 'précondition : Banner.jsx doit contenir un <h1> et un <img>');
        $this->assertContains('h1', array_column($targetTags, 'name'), 'précondition : Banner.jsx doit contenir un <h1>');
        $this->assertContains('img', array_column($targetTags, 'name'), 'précondition : Banner.jsx doit contenir un <img>');

        foreach ($targetTags as $tag) {
            $this->assertStringNotContainsString(
                'animate-kodem',
                $tag['attrs'],
                "animate-kodem trouvé sur un <{$tag['name']}> de Banner.jsx (élément LCP) : {$tag['attrs']}"
            );
        }
    }

    /**
     * Garde-fou complémentaire au test précédent : aucun `<h1>` de la
     * surface publique ne doit porter une classe `animate-kodem-*`, NI être
     * contenu dans un élément qui en porte une (ex. un wrapper
     * `<div className="animate-kodem-rise">` englobant le titre). Un
     * contrôle ligne à ligne est insuffisant ici : le wrapper animé et le
     * `<h1>` qu'il englobe se trouvent typiquement sur des lignes
     * différentes. `scanJsxTags` construit donc une pile des éléments
     * ouverts (comptage de balises équilibré) et, à chaque `<h1>` rencontré,
     * vérifie qu'aucun élément de la pile — c'est-à-dire aucun ancêtre — ne
     * porte `animate-kodem`.
     */
    public function test_no_h1_is_animated_on_public_surface(): void
    {
        $files = $this->publicSurfaceFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        $checkedAtLeastOneH1 = false;

        foreach ($files as $file) {
            $contents = $this->readRaw($file);
            $tags = $this->scanJsxTags($contents);

            /** @var list<array{name: string, animated: bool}> $stack */
            $stack = [];

            foreach ($tags as $tag) {
                if ($tag['closing']) {
                    // Dépile jusqu'au tag correspondant (tolérance aux
                    // incohérences de comptage plutôt qu'un crash du test).
                    while ($stack !== [] && end($stack)['name'] !== $tag['name']) {
                        array_pop($stack);
                    }

                    if ($stack !== []) {
                        array_pop($stack);
                    }

                    continue;
                }

                $isAnimated = str_contains($tag['attrs'], 'animate-kodem');

                if ($tag['name'] === 'h1') {
                    $checkedAtLeastOneH1 = true;

                    $this->assertStringNotContainsString(
                        'animate-kodem',
                        $tag['attrs'],
                        "un <h1> porte directement une classe animate-kodem dans {$file}"
                    );

                    foreach ($stack as $frame) {
                        $this->assertFalse(
                            $frame['animated'],
                            "un <h1> est contenu dans un ancêtre <{$frame['name']}> animé (animate-kodem) dans {$file}"
                        );
                    }
                }

                if (! $tag['selfClosing']) {
                    $stack[] = ['name' => $tag['name'], 'animated' => $isAnimated];
                }
            }
        }

        $this->assertTrue(
            $checkedAtLeastOneH1,
            'précondition : au moins un <h1> doit être scanné sur la surface publique'
        );
    }

    /**
     * Mesuré au Lighthouse le 2026-07-30 sur /contact (page SANS bannière),
     * serveur local, assets buildés, même FCP (2,6 s) dans les trois cas :
     *
     *   <p> d'intro en `animate-kodem-rise` (opacity 0→1 + translateY) : LCP 4,2 s, CLS 0
     *   <p> d'intro sans animation (contrôle)                         : LCP 3,6 s, CLS 0
     *   <p> d'intro en `animate-kodem-slide` (translateY seul)        : LCP 3,6 s, CLS 0
     *
     * Cause : Chrome ne retient pas comme candidat LCP un élément peint à
     * `opacity: 0`. Une animation d'entrée qui démarre à opacité nulle
     * (`animate-kodem-rise`, `animate-kodem-fade`) retarde donc le LCP de
     * toute sa durée dès lors qu'elle porte sur (ou englobe) l'élément LCP.
     * Sur les pages SANS bannière, le premier bloc de texte au-dessus de la
     * ligne de flottaison EST ce candidat LCP — contrairement aux pages avec
     * bannière, où l'élément LCP est l'`<img>` de la bannière, jamais animée
     * (cf. test_banner_lcp_elements_are_not_animated). Ce test protège donc
     * cette régression mesurée : sur toute page publique n'important PAS
     * `Banner`, seule la primitive sans opacité `animate-kodem-slide` est
     * autorisée pour une animation d'entrée.
     */
    public function test_banner_less_pages_use_opacity_free_entry_animation(): void
    {
        $files = array_merge(
            $this->globRecursive(base_path('resources/js/Pages/Public'), '*.jsx'),
            $this->globRecursive(base_path('resources/js/Pages/Audits'), '*.jsx')
        );
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        $checkedAtLeastOneBannerLessFile = false;

        foreach ($files as $file) {
            $contents = $this->readRaw($file);

            $importsBanner = (bool) preg_match(
                '/import\s+Banner\s+from\s+[\'"].*Banner[\'"]/',
                $contents
            );

            if ($importsBanner) {
                continue;
            }

            $checkedAtLeastOneBannerLessFile = true;

            $this->assertStringNotContainsString(
                'animate-kodem-rise',
                $contents,
                "animate-kodem-rise (opacity 0→1) trouvé dans {$file}, page sans bannière : retarde le LCP (mesuré 4,2 s vs 3,6 s, cf. docblock)"
            );
            $this->assertStringNotContainsString(
                'animate-kodem-fade',
                $contents,
                "animate-kodem-fade (opacity 0→1) trouvé dans {$file}, page sans bannière : retarde le LCP (mesuré 4,2 s vs 3,6 s, cf. docblock)"
            );
        }

        $this->assertTrue(
            $checkedAtLeastOneBannerLessFile,
            'précondition : au moins un fichier sans import de Banner doit être scanné'
        );
    }

    /**
     * `.kodem-reveal` et `.kodem-reveal-grid > *` pilotent chacun leur PROPRE
     * `animation-timeline: view()` : imbriquer l'un dans l'autre — dans
     * n'importe quel sens, et y compris la même classe deux fois — produit un
     * résultat incohérent (la timeline de l'enfant se calcule sur SA propre
     * entrée dans le viewport, indépendamment de celle du parent — l'enfant
     * peut donc apparaître avant, après, ou de façon désynchronisée par
     * rapport au parent qui l'englobe). Réutilise `scanJsxTags` et une pile
     * d'ancêtres (même mécanique que `test_no_h1_is_animated_on_public_surface`)
     * pour détecter tout `.kodem-reveal`/`.kodem-reveal-grid` dont un ancêtre
     * porte déjà l'une ou l'autre de ces deux classes.
     *
     * Détection par CLASSE EXACTE (`attrsHasExactClass`), jamais par
     * sous-chaîne : `kodem-reveal-grid` contient littéralement `kodem-reveal`,
     * un `str_contains` naïf confondrait donc les deux classes et ferait
     * échouer ce garde sur la moitié des grilles converties (cf. rapport
     * d'exécution — mutation rejouée pour le prouver).
     */
    public function test_kodem_reveal_is_never_nested(): void
    {
        $files = $this->publicSurfaceFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        $checkedAtLeastOneReveal = false;

        foreach ($files as $file) {
            $contents = $this->readRaw($file);
            $tags = $this->scanJsxTags($contents);

            /** @var list<array{name: string, revealed: bool}> $stack */
            $stack = [];

            foreach ($tags as $tag) {
                if ($tag['closing']) {
                    while ($stack !== [] && end($stack)['name'] !== $tag['name']) {
                        array_pop($stack);
                    }

                    if ($stack !== []) {
                        array_pop($stack);
                    }

                    continue;
                }

                $isRevealed = $this->attrsHasExactClass($tag['attrs'], 'kodem-reveal')
                    || $this->attrsHasExactClass($tag['attrs'], 'kodem-reveal-grid');

                if ($isRevealed) {
                    $checkedAtLeastOneReveal = true;

                    foreach ($stack as $frame) {
                        $this->assertFalse(
                            $frame['revealed'],
                            "un <{$tag['name']}> kodem-reveal/kodem-reveal-grid est imbriqué dans un ancêtre <{$frame['name']}> déjà kodem-reveal/kodem-reveal-grid dans {$file}"
                        );
                    }
                }

                if (! $tag['selfClosing']) {
                    $stack[] = ['name' => $tag['name'], 'revealed' => $isRevealed];
                }
            }
        }

        $this->assertTrue(
            $checkedAtLeastOneReveal,
            'précondition : au moins un élément kodem-reveal ou kodem-reveal-grid doit être scanné sur la surface publique'
        );
    }

    /**
     * `animation-delay` (utilisé par `.kodem-stagger` pour décaler ses
     * enfants) est ignoré sur une timeline `view()` (celle de `.kodem-reveal`
     * ET de `.kodem-reveal-grid`) : combiner `.kodem-stagger` avec l'une ou
     * l'autre sur un même élément est un bug silencieux — la cascade de
     * décalage ne produit visuellement aucun effet, sans qu'aucune erreur ne
     * le signale. Ce test l'interdit explicitement, tag par tag (pas
     * seulement au niveau ancêtre : les classes doivent simplement ne jamais
     * cohabiter dans le même attribut `className`). Détection par classe
     * EXACTE (`attrsHasExactClass`) pour `kodem-reveal`/`kodem-reveal-grid` —
     * même piège de sous-chaîne que `test_kodem_reveal_is_never_nested`.
     */
    public function test_stagger_and_reveal_are_never_combined(): void
    {
        $files = $this->publicSurfaceFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        $checkedAtLeastOneStaggerOrReveal = false;

        foreach ($files as $file) {
            $contents = $this->readRaw($file);
            $tags = $this->scanJsxTags($contents);

            foreach ($tags as $tag) {
                if ($tag['closing']) {
                    continue;
                }

                $hasStagger = str_contains($tag['attrs'], 'kodem-stagger');
                $hasReveal = $this->attrsHasExactClass($tag['attrs'], 'kodem-reveal')
                    || $this->attrsHasExactClass($tag['attrs'], 'kodem-reveal-grid');

                if ($hasStagger || $hasReveal) {
                    $checkedAtLeastOneStaggerOrReveal = true;
                }

                $this->assertFalse(
                    $hasStagger && $hasReveal,
                    "un <{$tag['name']}> combine kodem-stagger et kodem-reveal/kodem-reveal-grid dans {$file} : animation-delay est ignoré sur la timeline view() (cf. §4)"
                );
            }
        }

        $this->assertTrue(
            $checkedAtLeastOneStaggerOrReveal,
            'précondition : au moins un élément kodem-stagger ou kodem-reveal/kodem-reveal-grid doit être scanné sur la surface publique'
        );
    }

    /**
     * `animation-range` gouverne seule la vitesse perçue sur une timeline
     * `view()` (cf. §4) : une valeur atteignant `cover 100%` (ou au-delà)
     * ferait dépendre la fin de la révélation d'un point de défilement que
     * l'utilisateur peut ne jamais atteindre (bas de page), laissant la carte
     * bloquée à opacité nulle. Ce test borne TOUTE occurrence de
     * `animation-range` dans `app.css` — qu'elle vienne de `.kodem-reveal` ou
     * des paliers `.kodem-reveal-grid--N` — strictement en dessous de 100 %.
     */
    public function test_reveal_grid_range_stays_below_full_cover(): void
    {
        $css = $this->readRaw(base_path('resources/css/app.css'));

        $this->assertMatchesRegularExpression(
            '/animation-range\s*:/',
            $css,
            'précondition : au moins une déclaration animation-range doit être scannée'
        );

        preg_match_all('/animation-range\s*:\s*entry\s+0%\s+cover\s+(\d+(?:\.\d+)?)%/', $css, $matches);

        $this->assertNotEmpty(
            $matches[1],
            'précondition : au moins une valeur "cover N%" doit être extraite de animation-range'
        );

        foreach ($matches[1] as $coverValue) {
            $this->assertLessThan(
                100,
                (float) $coverValue,
                "une valeur animation-range atteint ou dépasse cover 100% ({$coverValue}%) : un élément pourrait rester bloqué à opacité nulle"
            );
        }
    }
}
