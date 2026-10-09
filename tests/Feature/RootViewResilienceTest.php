<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\VitrineContentUnreadable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Tests\Concerns\ReadsJsonLd;
use Tests\Concerns\ServesTemporaryPositioning;
use Tests\TestCase;

/**
 * app.blade.php is the root view of every Inertia page, admin and payment returns included:
 * an unreadable positioning file must degrade the JSON-LD, never the page.
 */
class RootViewResilienceTest extends TestCase
{
    use ReadsJsonLd;
    use RefreshDatabase;
    use ServesTemporaryPositioning;

    private const FALLBACK_DESCRIPTION = 'KODEM conçoit des outils métier sur mesure qui suppriment les tâches manuelles.';

    protected function tearDown(): void
    {
        $this->restorePositioning();

        parent::tearDown();
    }

    public function test_login_page_renders_with_the_fallback_organization_when_positioning_is_unreadable(): void
    {
        Exceptions::fake();
        $this->withoutVite();
        $this->servePositioning(null);

        $graph = collect($this->jsonLdGraph($this->get('/login')->assertOk()));

        $organization = $graph->firstWhere('@type', 'Organization');
        $service = $graph->firstWhere('@id', url('/#service-outils-metier'));

        $this->assertNotNull($organization, 'le graphe doit garder son nœud Organization');
        $this->assertNotNull($service, 'le graphe doit garder son nœud Service outils métier');
        $this->assertSame(self::FALLBACK_DESCRIPTION, $organization['description']);
        $this->assertSame([], $organization['knowsAbout']);
        $this->assertSame(self::FALLBACK_DESCRIPTION, $service['description']);
        Exceptions::assertReported(VitrineContentUnreadable::class);
    }
}
