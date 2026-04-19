<?php

namespace Tests\Unit;

use App\Services\PrestationCatalog;
use Tests\TestCase;

class PrestationCatalogTest extends TestCase
{
    public function test_catalog_contains_required_keywords(): void
    {
        $slugs = array_column(PrestationCatalog::all(), 'slug');

        foreach (['audit-seo', 'audit-securite', 'hebergement-web', 'developpement-web', 'creation-saas'] as $expected) {
            $this->assertContains($expected, $slugs, "Missing prestation: {$expected}");
        }
    }

    public function test_teaser_is_a_subset_of_catalog(): void
    {
        $this->assertLessThanOrEqual(count(PrestationCatalog::all()), count(PrestationCatalog::teaser()));
        $this->assertGreaterThan(0, count(PrestationCatalog::teaser()));
    }

    public function test_each_prestation_has_required_fields(): void
    {
        foreach (PrestationCatalog::all() as $p) {
            foreach (['slug', 'title', 'price_label', 'tagline', 'description', 'features', 'cta'] as $k) {
                $this->assertArrayHasKey($k, $p);
            }
        }
    }
}
