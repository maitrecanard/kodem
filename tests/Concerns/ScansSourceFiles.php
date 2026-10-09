<?php

declare(strict_types=1);

namespace Tests\Concerns;

/**
 * Lecture des fichiers sources pour les gardes statiques : le SSR étant
 * inactif en test, la copy écrite en JSX ne se vérifie qu'en lisant le code.
 */
trait ScansSourceFiles
{
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

    /**
     * Contenu du fichier, chaque suite de blancs réduite à une espace : une phrase
     * coupée sur plusieurs lignes par le formateur reste détectable.
     */
    private function readNormalized(string $path): string
    {
        $this->assertTrue(is_readable($path), "le fichier attendu est illisible : {$path}");

        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "la lecture du fichier a échoué : {$path}");

        return preg_replace('/\s+/u', ' ', $contents);
    }
}
