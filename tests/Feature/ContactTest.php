<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('contact');
    }

    public function test_it_stores_a_valid_contact_message(): void
    {
        $payload = [
            'name' => 'Alice Dupont',
            'email' => 'alice@exemple.fr',
            'company' => 'Société X',
            'subject' => 'Demande de devis SaaS',
            'message' => 'Bonjour, je souhaite un devis pour la création d\'un SaaS.',
        ];

        $response = $this->post('/contact', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Alice Dupont',
            'email' => 'alice@exemple.fr',
            'subject' => 'Demande de devis SaaS',
            'status' => 'new',
        ]);
    }

    public function test_it_rejects_invalid_payload(): void
    {
        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => 'short',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_silently_discards_spam(): void
    {
        $this->post('/contact', [
            'name' => 'Spam Bot',
            'email' => 'spam@spam.ru',
            'subject' => 'Viagra cheap',
            'message' => 'Click here for cheap medicine',
            'website' => 'http://spam.ru',
        ])->assertRedirect();

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_rate_limit_blocks_after_5_per_minute(): void
    {
        $payload = [
            'name' => 'Tester',
            'email' => 't@t.fr',
            'subject' => 'Sujet de test',
            'message' => 'Un message valide pour passer la validation.',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $payload)->assertRedirect();
        }

        $this->post('/contact', $payload)->assertStatus(429);
    }
}
