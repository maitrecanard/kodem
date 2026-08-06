<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Garde-fou anti-régression orthographique et anti-doublon de <title>.
 *
 * Le SSR étant inactif en test, aucun test Feature ne peut vérifier le HTML
 * rendu des composants React : ces assertions inspectent directement le code
 * source JSX/JSON/PHP/Blade, comme CopyGeoGuardTest.
 */
class SpellingGuardTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function scannableFiles(): array
    {
        $files = $this->globRecursive(base_path('resources/js'), '*.jsx');
        $files = array_merge($files, $this->globRecursive(base_path('resources/views'), '*.blade.php'));
        $files = array_merge($files, $this->globRecursive(base_path('app'), '*.php'));
        $files = array_merge($files, glob(base_path('content/*.json')) ?: []);

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

    private function readNormalized(string $path): string
    {
        $this->assertTrue(is_readable($path), "le fichier attendu est illisible : {$path}");

        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "la lecture du fichier a échoué : {$path}");

        return preg_replace('/\s+/u', ' ', $contents);
    }

    /**
     * Fautes corrigées en v1.16.1 : chacune doit rester absente de tout le corpus.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function misspellingProvider(): array
    {
        return [
            'trés au lieu de très' => ['trés', 'très'],
            'en-dessous avec trait d\'union' => ['en-dessous', 'en dessous'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('misspellingProvider')]
    public function test_corrected_misspelling_never_reappears(string $faute, string $correction): void
    {
        $files = $this->scannableFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        foreach ($files as $file) {
            $this->assertStringNotContainsString(
                $faute,
                $this->readNormalized($file),
                "faute d'orthographe \"{$faute}\" réintroduite dans {$file} — écrire \"{$correction}\""
            );
        }
    }

    public function test_testimonial_quote_is_spelled_correctly(): void
    {
        $normalized = $this->readNormalized(base_path('content/testimonials.json'));

        // Témoignage réel affiché sur la page d'accueil : la faute y était visible
        // par tout visiteur. La majuscule après le point fait partie de la correction.
        $this->assertStringContainsString(
            'Un très bon développeur, à l\'écoute, il fait du super bon travail. Je le recommande vivement',
            $normalized,
            'le témoignage corrigé a été altéré : la faute "trés" ou la minuscule après le point est revenue'
        );
    }

    public function test_root_view_declares_no_title_tag(): void
    {
        $normalized = $this->readNormalized(base_path('resources/views/app.blade.php'));

        // Les commentaires Blade et HTML sont retirés : celui qui documente cette règle
        // cite justement la balise, et ferait échouer le test sur lui-même.
        $withoutComments = preg_replace('/\{\{--.*?--\}\}|<!--.*?-->/us', '', $normalized);

        $this->assertNotNull($withoutComments, 'le retrait des commentaires a échoué');

        // Un <title> en dur ici produit un SECOND titre dans le HTML brut, placé
        // AVANT celui rendu par @inertiaHead. La spec HTML n'en admet qu'un et c'est
        // le premier qui fait foi : les crawlers lisaient "Kodem" sur toutes les pages.
        $this->assertStringNotContainsString(
            '<title',
            $withoutComments,
            'app.blade.php ne doit déclarer aucun <title> : il ferait doublon avec celui du SSR'
        );

        $this->assertStringContainsString(
            '@inertiaHead',
            $normalized,
            'précondition : @inertiaHead doit rester présent, c\'est lui qui rend le titre'
        );
    }

    public function test_both_entrypoints_build_the_title_through_the_shared_helper(): void
    {
        foreach (['resources/js/app.jsx', 'resources/js/ssr.jsx'] as $entrypoint) {
            $normalized = $this->readNormalized(base_path($entrypoint));

            // Client et SSR doivent produire le MÊME titre, sinon le titre rendu côté
            // serveur diffère de celui posé au montage.
            $this->assertStringContainsString(
                'title: (title) => pageTitle(title, appName)',
                $normalized,
                "{$entrypoint} doit construire son titre via le helper partagé pageTitle"
            );
        }
    }

    public function test_title_helper_does_not_append_an_already_present_brand(): void
    {
        $normalized = $this->readNormalized(base_path('resources/js/lib/pageTitle.js'));

        // Les meta titles publics contiennent déjà « | Kodem » ; sans cette garde on
        // retombe sur « … | Kodem - Kodem ».
        $this->assertStringContainsString(
            'title.includes(appName) ? title : `${title} - ${appName}`',
            $normalized,
            'pageTitle doit sauter le suffixe quand le titre porte déjà la marque'
        );
    }
}
