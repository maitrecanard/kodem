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

    // -------------------------------------------------------------------------
    // cases()
    // -------------------------------------------------------------------------

    public function test_cases_returns_two_entries_with_expected_slugs(): void
    {
        $cases = VitrineContent::cases();

        $this->assertCount(2, $cases, 'cases() doit retourner exactement 2 entrées');

        $slugs = array_column($cases, 'slug');
        $this->assertContains('photomaton', $slugs);
        $this->assertContains('ecran-raspberry', $slugs);
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

    public function test_testimonials_returns_empty_array(): void
    {
        $testimonials = VitrineContent::testimonials();

        $this->assertIsArray($testimonials);
        $this->assertCount(0, $testimonials, 'testimonials() doit être vide (testimonials.json = [])');
    }

    // -------------------------------------------------------------------------
    // testimonialsForCase()
    // -------------------------------------------------------------------------

    public function test_testimonials_for_case_photomaton_returns_empty_array_without_error(): void
    {
        $result = VitrineContent::testimonialsForCase('photomaton');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
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
