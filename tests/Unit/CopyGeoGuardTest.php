<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Garde-fou anti-dérive géographique sur la copy JSX.
 *
 * Le SSR étant inactif en test, aucun test Feature ne peut vérifier le HTML
 * rendu des composants React : ces assertions inspectent directement le
 * code source JSX/JSON/PHP.
 */
class CopyGeoGuardTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function scannableFiles(): array
    {
        $files = $this->globRecursive(base_path('resources/js'), '*.jsx');
        $files = array_merge($files, $this->globRecursive(base_path('resources/views'), '*.blade.php'));
        $files = array_merge($files, glob(base_path('content/*.json')) ?: []);
        $files = array_merge($files, glob(base_path('app/Http/Controllers/*.php')) ?: []);

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

    public function test_no_ungraduated_same_day_promise_anywhere(): void
    {
        $files = $this->scannableFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        foreach ($files as $file) {
            $normalized = $this->readNormalized($file);

            $offset = 0;
            while (($pos = mb_strpos($normalized, 'jour même', $offset)) !== false) {
                $window = mb_substr($normalized, $pos, 140);

                $this->assertStringContainsString(
                    'Nouvelle-Aquitaine',
                    $window,
                    "promesse \"jour même\" non graduée dans {$file} — publicité trompeuse"
                );

                $offset = $pos + mb_strlen('jour même');
            }
        }
    }

    public function test_no_regional_service_area_claim_remains(): void
    {
        $files = $this->scannableFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        $forbiddenPrecedingWords = [
            'intervient', 'desservi', 'zone', 'déploie', 'couvre',
            'intervention', 'local', 'présen',
        ];

        // La promesse est explicitement graduée quand "Nouvelle-Aquitaine" est
        // immédiatement suivie de son pendant national (ex. "sous 48 h ailleurs") :
        // ce n'est alors plus une promesse de couverture régionale non graduée.
        $gradedQualifiers = ['ailleurs', '48 h'];

        foreach ($files as $file) {
            $normalized = $this->readNormalized($file);

            $offset = 0;
            while (($pos = mb_strpos($normalized, 'Nouvelle-Aquitaine', $offset)) !== false) {
                $windowStart = max(0, $pos - 60);
                $preceding = mb_strtolower(mb_substr($normalized, $windowStart, $pos - $windowStart));
                $following = mb_strtolower(mb_substr($normalized, $pos, 80));

                $isGraduated = false;
                foreach ($gradedQualifiers as $qualifier) {
                    if (mb_strpos($following, $qualifier) !== false) {
                        $isGraduated = true;
                        break;
                    }
                }

                if (! $isGraduated) {
                    foreach ($forbiddenPrecedingWords as $word) {
                        $this->assertDoesNotMatchRegularExpression(
                            '/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'(?![\p{L}\p{N}])/u',
                            $preceding,
                            "promesse de couverture régionale non graduée dans {$file} — \"{$word}\" précède \"Nouvelle-Aquitaine\""
                        );
                    }
                }

                $offset = $pos + mb_strlen('Nouvelle-Aquitaine');
            }
        }
    }
}
