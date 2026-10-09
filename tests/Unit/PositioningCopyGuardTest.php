<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ScansSourceFiles;
use Tests\TestCase;

/**
 * Garde des formulations interdites par le positionnement « outils métier sur
 * mesure » (claude.md §1, §3, §5 et §7).
 */
class PositioningCopyGuardTest extends TestCase
{
    use ScansSourceFiles;

    /**
     * Espace ordinaire, entité HTML ou espace insécable (normale et fine) : une
     * formulation interdite reste interdite quelle que soit l'espace qui la coupe.
     */
    private const SPACE = '(?:\s|&nbsp;|\x{00A0}|\x{202F})';

    /**
     * @return list<string>
     */
    private function copyBearingFiles(): array
    {
        return array_values(array_unique(array_merge(
            $this->globRecursive(base_path('resources/js/Pages/Public'), '*.jsx'),
            $this->globRecursive(base_path('resources/js/Pages/Audits'), '*.jsx'),
            [base_path('resources/js/Layouts/PublicLayout.jsx')],
            glob(base_path('resources/js/Components/*.jsx')) ?: [],
            $this->globRecursive(base_path('resources/views'), '*.blade.php'),
            glob(base_path('content/*.json')) ?: [],
            glob(base_path('app/Http/Controllers/*.php')) ?: [],
            [base_path('app/Services/PrestationCatalog.php')],
        )));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function forbiddenWordingProvider(): array
    {
        $space = self::SPACE;
        $patterns = [
            '20 ans' => "/(?<![\\p{N}])20{$space}+ans(?!\\p{L})/iu",
            'administrateur système' => "/administrateurs?{$space}+systèmes?/iu",
            // « freelance » n'est plus interdit : décision de l'actionnaire du 2026-10-09
            // (registre des décisions), l'accueil reprend la maquette qui l'emploie.
            'nos équipes' => "/nos{$space}+équipes/iu",
            '619 tests unitaires' => "/619{$space}+tests?{$space}+unitaires/iu",
            '2 500 utilisateurs' => "/2{$space}?500{$space}+utilisateurs/iu",
            'dispositifs connectés' => "/dispositifs?{$space}+connect[ée]s?/iu",
            'contact@kodem.fr' => '/contact@kodem\\.fr/iu',
            // Vote du 2026-09-29 : promesses sans réalisation derrière.
            'opérateur MUXEN' => "/opérateur{$space}+(?:réseau|MUXEN)/iu",
            'inspections' => '/inspections?/iu',
            'synchronisations entre logiciels' => "/synchronisations?{$space}+entre{$space}+logiciels/iu",
            'depuis 5 ans' => "/depuis{$space}+5{$space}+ans/iu",
            'monitoring 24/7' => "/24{$space}?\\/{$space}?7/iu",
            'WAF' => '/\\bWAF\\b/u',
            'protection DDoS' => '/DDoS/iu',
            'sites vitrines' => "/sites?{$space}+(?:internet{$space}+)?vitrines?/iu",
            'hébergement inclus' => "/hébergement{$space}+inclus/iu",
            'remédiation' => '/remédiation/iu',
        ];

        $datasets = [];
        foreach ($patterns as $label => $pattern) {
            $datasets[$label] = [$label, $pattern];
        }

        return $datasets;
    }

    #[DataProvider('forbiddenWordingProvider')]
    public function test_forbidden_wording_never_appears_on_the_public_surface(string $label, string $pattern): void
    {
        $files = $this->copyBearingFiles();
        $this->assertNotEmpty($files, 'précondition : au moins un fichier doit être scanné');

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $this->readNormalized($file),
                "formulation interdite « {$label} » trouvée dans {$file}"
            );
        }
    }
}
