<?php

namespace Tests\Unit;

use App\Services\VitrineContent;
use Tests\TestCase;

class VitrineContentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // positioning()
    // -------------------------------------------------------------------------

    public function test_positioning_returns_non_empty_array_with_expected_keys(): void
    {
        $positioning = VitrineContent::positioning();

        $this->assertIsArray($positioning);
        $this->assertNotEmpty($positioning);

        foreach (['hero_title', 'hero_baseline', 'value_prop', 'capabilities', 'secteurs'] as $key) {
            $this->assertArrayHasKey($key, $positioning, "positioning() doit contenir la clé '{$key}'");
        }
    }

    public function test_positioning_announces_national_coverage_and_keeps_the_poitiers_base(): void
    {
        $positioning = VitrineContent::positioning();

        $this->assertStringContainsString('partout en France', $positioning['hero_baseline']);
        $this->assertStringNotContainsString('Nouvelle-Aquitaine', $positioning['hero_baseline']);

        $this->assertStringContainsString('Poitiers', $positioning['ancrage_local']);
        $this->assertStringContainsString('France', $positioning['ancrage_local']);

        foreach ($positioning['secteurs'] as $secteur) {
            $this->assertStringNotContainsString('France', $secteur, "le secteur '{$secteur}' ne doit pas porter de mention géographique");
            $this->assertStringNotContainsString('Poitiers', $secteur, "le secteur '{$secteur}' ne doit pas porter de mention géographique");
            $this->assertStringNotContainsString('Aquitaine', $secteur, "le secteur '{$secteur}' ne doit pas porter de mention géographique");
        }
    }

    // -------------------------------------------------------------------------
    // cases()
    // -------------------------------------------------------------------------

    public function test_cases_returns_the_four_published_cases(): void
    {
        $cases = VitrineContent::cases();

        $this->assertCount(4, $cases, 'cases() doit retourner exactement 4 entrées');

        $slugs = array_column($cases, 'slug');
        $this->assertContains('photomaton', $slugs);
        $this->assertContains('ecran-raspberry', $slugs);
        $this->assertContains('controle-acces-billetterie', $slugs);
        $this->assertContains('carte-qr-manhattan-cafe', $slugs);
    }

    // -------------------------------------------------------------------------
    // case()
    // -------------------------------------------------------------------------

    public function test_case_photomaton_returns_matching_case(): void
    {
        $cas = VitrineContent::case('photomaton');

        $this->assertNotNull($cas);
        $this->assertIsArray($cas);
        $this->assertSame('photomaton', $cas['slug']);
    }

    public function test_case_does_not_exist_returns_null(): void
    {
        $this->assertNull(VitrineContent::case('does-not-exist'));
    }

    // -------------------------------------------------------------------------
    // testimonials()
    // -------------------------------------------------------------------------

    public function test_testimonials_returns_five_real_named_entries(): void
    {
        $testimonials = VitrineContent::testimonials();

        $this->assertCount(5, $testimonials, 'testimonials() doit contenir les 5 témoignages réels importés');

        foreach ($testimonials as $t) {
            $this->assertSame(
                ['citation', 'nom', 'fonction', 'structure', 'photo'],
                array_keys($t),
                'chaque témoignage doit exposer exactement les clés citation/nom/fonction/structure/photo'
            );

            foreach (['citation', 'nom', 'fonction', 'structure'] as $field) {
                $value = trim($t[$field]);
                $this->assertNotSame('', $value, "le champ '{$field}' ne doit pas être vide");
                $this->assertStringStartsNotWith('// TODO', $value, "le champ '{$field}' ne doit pas être une sentinelle TODO");
            }

            $this->assertNotSame('Prénom Nom', $t['nom']);

            $this->assertArrayNotHasKey('rating', $t, 'aucun témoignage ne doit porter de note chiffrée (§4)');
            $this->assertArrayNotHasKey('exemple', $t);
            $this->assertArrayNotHasKey('email', $t, 'aucune donnée PII email ne doit être exposée');
        }

        $this->assertContains('MUXEN', array_column($testimonials, 'structure'));
    }

    // -------------------------------------------------------------------------
    // Guard-rail: any future cas_lie must point to an existing case
    // -------------------------------------------------------------------------

    public function test_testimonials_have_no_orphan_case_link(): void
    {
        $testimonials = VitrineContent::testimonials();

        $this->assertIsArray($testimonials, 'précondition : testimonials() doit rester lisible pour vérifier les cas_lie');

        foreach ($testimonials as $t) {
            if (! empty($t['cas_lie'] ?? null)) {
                $this->assertNotNull(
                    VitrineContent::case($t['cas_lie']),
                    "cas_lie '{$t['cas_lie']}' doit correspondre à un cas existant"
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // testimonialsForCase()
    // -------------------------------------------------------------------------

    public function test_testimonials_for_case_returns_empty_for_every_published_case(): void
    {
        foreach (VitrineContent::cases() as $cas) {
            $result = VitrineContent::testimonialsForCase($cas['slug']);

            $this->assertIsArray($result);
            $this->assertSame([], $result, "testimonialsForCase('{$cas['slug']}') doit être vide (aucun cas_lie renseigné)");
        }
    }

    // -------------------------------------------------------------------------
    // Case images: real files must be valid WebP < 300ko
    // -------------------------------------------------------------------------

    public function test_case_images_are_real_webp_files_under_300ko(): void
    {
        $realImagesFound = 0;

        foreach (VitrineContent::cases() as $cas) {
            $image = $cas['image'];

            if ($image === '' || str_starts_with($image, '// TODO')) {
                continue;
            }

            $realImagesFound++;

            $this->assertMatchesRegularExpression(
                '#^/images/realisations/[\w-]+\.webp$#',
                $image,
                "l'image du cas '{$cas['slug']}' doit respecter le contrat de CaseImage.jsx"
            );

            $path = public_path(ltrim($image, '/'));
            $this->assertFileExists($path, "le fichier image du cas '{$cas['slug']}' doit exister sur le disque");
            $this->assertLessThan(300 * 1024, filesize($path), "l'image du cas '{$cas['slug']}' doit peser moins de 300 ko");

            $bytes = file_get_contents($path, false, null, 0, 12);
            $this->assertSame('RIFF', substr($bytes, 0, 4), "l'image du cas '{$cas['slug']}' doit être un WebP valide (en-tête RIFF)");
            $this->assertSame('WEBP', substr($bytes, 8, 4), "l'image du cas '{$cas['slug']}' doit être un WebP valide (fourcc WEBP)");
        }

        $this->assertGreaterThanOrEqual(1, $realImagesFound, 'au moins un cas doit exposer une image réelle');
    }

    /**
     * Les 4 cas sont désormais illustrés. Ce test garantit qu'un cas ne peut
     * jamais retomber dans un état intermédiaire silencieux : soit une image
     * réelle conforme au contrat de CaseImage.jsx, soit une sentinelle `// TODO`
     * explicite qui déclenche la réserve d'image. Jamais une chaîne vide, un
     * chemin externe ou une valeur non-chaîne, qui produiraient une image cassée.
     */
    public function test_every_case_image_is_either_a_real_path_or_an_explicit_sentinel(): void
    {
        $cases = VitrineContent::cases();
        $this->assertNotEmpty($cases);

        foreach ($cases as $cas) {
            $image = $cas['image'] ?? null;

            $this->assertIsString($image, "le cas '{$cas['slug']}' doit porter un champ image de type chaîne");
            $this->assertNotSame('', trim($image), "le cas '{$cas['slug']}' ne doit jamais avoir un champ image vide");

            $isSentinel = str_starts_with($image, '// TODO');
            $isRealPath = (bool) preg_match('#^/images/realisations/[\w-]+\.webp$#', $image);

            $this->assertTrue(
                $isSentinel || $isRealPath,
                "l'image du cas '{$cas['slug']}' doit être soit une sentinelle '// TODO', soit un chemin /images/realisations/<slug>.webp — reçu : {$image}"
            );
        }
    }

    /**
     * `image_focus` déplace l'ancrage du recadrage object-cover (cf. CaseImage.jsx).
     * Une valeur hors liste blanche retomberait silencieusement sur 'center' :
     * on la refuse ici pour attraper une faute de frappe dans le contenu.
     */
    public function test_case_image_focus_uses_only_whitelisted_values(): void
    {
        $allowed = ['top', 'center', 'bottom', 'left', 'right'];

        foreach (VitrineContent::cases() as $cas) {
            if (! array_key_exists('image_focus', $cas)) {
                continue;
            }

            $this->assertContains(
                $cas['image_focus'],
                $allowed,
                "le cas '{$cas['slug']}' utilise un image_focus inconnu de CaseImage.jsx"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Edge cases — case() is a pure array lookup, not FS access
    // -------------------------------------------------------------------------

    public function test_case_empty_string_returns_null(): void
    {
        $this->assertNull(VitrineContent::case(''));
    }

    public function test_case_path_traversal_returns_null(): void
    {
        // Confirms slug lookup is a collect()->firstWhere() — no filesystem path is built.
        $this->assertNull(VitrineContent::case('../../.env'));
    }
}
