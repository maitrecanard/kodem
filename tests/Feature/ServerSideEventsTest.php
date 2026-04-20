<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ServerSideEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('audit');
        RateLimiter::clear('contact');
    }

    public function test_audit_submit_and_complete_are_tracked_server_side(): void
    {
        Http::fake(['*' => Http::response('<html><body></body></html>', 200, [])]);

        $this->post('/audit', ['url' => 'https://foo.example']);

        $this->assertTrue(Event::where('type', 'audit.submitted')->exists());
        $this->assertTrue(
            Event::where('type', 'audit.completed')->exists()
            || Event::where('type', 'audit.failed')->exists()
        );
    }

    public function test_audit_paid_event_is_recorded(): void
    {
        $audit = Audit::create([
            'url' => 'https://pay.example',
            'status' => 'completed',
            'score_total' => 70,
            'price_cents' => 2900,
            'pdf_price_cents' => 900,
            'cwv_price_cents' => 1900,
            'ip_hash' => str_repeat('a', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])->assertRedirect();

        $this->assertTrue(Event::where('type', 'audit.paid')
            ->whereJsonContains('metadata->audit_uuid', $audit->uuid)
            ->exists());
    }

    public function test_contact_submit_records_event(): void
    {
        $this->post('/contact', [
            'name' => 'Alice',
            'email' => 'a@a.fr',
            'subject' => 'Hello Kodem',
            'message' => 'Un message suffisamment long pour passer la validation.',
        ])->assertRedirect();

        $this->assertTrue(Event::where('type', 'contact.submitted')->exists());
    }

    public function test_contact_honeypot_records_spam_event(): void
    {
        $this->post('/contact', [
            'name' => 'Bot',
            'email' => 'b@b.fr',
            'subject' => 'spam',
            'message' => 'nope',
            'website' => 'http://spam.ru',
        ]);

        $this->assertTrue(Event::where('type', 'contact.spam_blocked')->exists());
    }

    public function test_pdf_download_records_event_for_admin(): void
    {
        $audit = Audit::create([
            'url' => 'https://p.example',
            'status' => 'completed',
            'score_seo' => 80, 'score_security' => 90, 'score_total' => 85,
            'results' => ['seo' => ['checks' => []], 'security' => ['checks' => []]],
            'price_cents' => 2900,
            'paid_at' => now(),
            'pdf_price_cents' => 900,
            'cwv_price_cents' => 1900,
            'ip_hash' => str_repeat('b', 64),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/audit/'.$audit->uuid.'/pdf')->assertOk();

        $evt = Event::where('type', 'pdf.downloaded')->first();
        $this->assertNotNull($evt);
        $this->assertSame($audit->uuid, $evt->metadata['audit_uuid']);
        $this->assertTrue((bool) $evt->metadata['is_admin']);
    }

    public function test_monitoring_subscribe_and_cancel_record_events(): void
    {
        $this->post('/monitoring/subscribe', [
            'url' => 'https://m.example',
            'email' => 'm@m.fr',
            'confirm' => '1',
        ])->assertRedirect();

        $this->assertTrue(Event::where('type', 'monitoring.subscribed')->exists());

        $sub = \App\Models\MonitoringSubscription::first();
        $this->post('/monitoring/'.$sub->token.'/cancel')->assertRedirect();

        $this->assertTrue(Event::where('type', 'monitoring.cancelled')->exists());
    }
}
