<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_seo_meta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Home')
                ->where('meta.title', fn ($t) => str_contains(strtolower($t), 'kodem'))
                ->where('meta.keywords', fn ($k) => str_contains($k, 'audit SEO')
                    && str_contains($k, 'audit de sécurité')
                    && str_contains($k, 'développement web')
                    && str_contains($k, 'hébergement web')
                    && str_contains($k, 'création de saas'))
                ->has('prestations', 4)
            );
    }

    public function test_services_page_renders(): void
    {
        $this->get('/prestations')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Services')
                ->has('prestations', fn ($p) => $p->etc())
            );
    }

    public function test_contact_page_renders(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Contact'));
    }

    public function test_mentions_legales_page_renders(): void
    {
        $this->get('/mentions-legales')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Mentions'));
    }

    public function test_cgv_page_renders(): void
    {
        $this->get('/cgv')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Cgv'));
    }

    public function test_audit_page_renders_with_form(): void
    {
        $this->get('/audit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Audit')
                ->has('paidPrestations')
            );
    }
}
