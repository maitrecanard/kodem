<?php

namespace Tests\Feature;

use App\Mail\AuditFollowupMail;
use App\Models\Audit;
use App\Services\DiscordNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DiscordNotifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'audit.discord_webhook_url' => 'https://discord.example/webhook/abc',
            'audit.discord_enabled' => true,
        ]);
    }

    protected function makeAudit(array $overrides = []): Audit
    {
        $createdAt = $overrides['created_at'] ?? now()->subDays(8);
        unset($overrides['created_at'], $overrides['updated_at']);

        $audit = Audit::create(array_merge([
            'url' => 'https://discord-test.example',
            'email' => 'lead@exemple.fr',
            'type' => 'full',
            'status' => 'completed',
            'score_seo' => 50,
            'score_security' => 40,
            'score_total' => 45,
        ], $overrides));

        Audit::query()->where('id', $audit->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $audit->fresh();
    }

    public function test_notifier_skips_when_disabled(): void
    {
        config(['audit.discord_enabled' => false]);
        Http::fake();

        $audit = $this->makeAudit();
        app(DiscordNotifier::class)->notifyAuditEvent(DiscordNotifier::EVENT_COMPLETED, $audit);

        Http::assertNothingSent();
    }

    public function test_notifier_skips_when_url_blank(): void
    {
        config(['audit.discord_webhook_url' => '']);
        Http::fake();

        $audit = $this->makeAudit();
        app(DiscordNotifier::class)->notifyAuditEvent(DiscordNotifier::EVENT_COMPLETED, $audit);

        Http::assertNothingSent();
    }

    public function test_notifier_posts_embed_to_webhook(): void
    {
        Http::fake(['discord.example/*' => Http::response('', 204)]);

        $audit = $this->makeAudit();
        app(DiscordNotifier::class)->notifyAuditEvent(DiscordNotifier::EVENT_COMPLETED, $audit, ['Type' => 'full']);

        Http::assertSent(function ($req) {
            $body = $req->data();
            return str_contains($req->url(), 'discord.example/webhook/abc')
                && isset($body['embeds'][0])
                && str_contains($body['embeds'][0]['title'], 'Audit terminé');
        });
    }

    public function test_silent_fail_on_http_error(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('discord down');
        });

        $audit = $this->makeAudit();
        // Doit pas lever d'exception
        app(DiscordNotifier::class)->notifyAuditEvent(DiscordNotifier::EVENT_COMPLETED, $audit);

        $this->assertTrue(true);
    }

    public function test_audit_submission_triggers_discord_events(): void
    {
        Http::fake([
            'discord.example/*' => Http::response('', 204),
            '*' => Http::response('<html><head><title>OK</title></head><body><h1>Hi</h1></body></html>', 200, [
                'content-type' => 'text/html',
            ]),
        ]);

        $this->post('/audit', [
            'url' => 'https://example.com',
            'type' => 'full',
        ])->assertRedirect();

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'discord.example')
                && str_contains($req->data()['embeds'][0]['title'] ?? '', 'Nouvel audit demandé');
        });
        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'discord.example')
                && str_contains($req->data()['embeds'][0]['title'] ?? '', 'Audit terminé');
        });
    }

    public function test_payment_triggers_discord_paid_event(): void
    {
        Http::fake(['discord.example/*' => Http::response('', 204)]);

        $audit = $this->makeAudit(['status' => 'completed']);

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])
            ->assertRedirect();

        Http::assertSent(fn ($req) => str_contains($req->url(), 'discord.example')
            && str_contains($req->data()['embeds'][0]['title'] ?? '', 'Audit payé'));
    }

    public function test_followup_command_triggers_discord_event(): void
    {
        Mail::fake();
        Http::fake(['discord.example/*' => Http::response('', 204)]);

        $this->makeAudit();

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertSent(AuditFollowupMail::class);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'discord.example')
            && str_contains($req->data()['embeds'][0]['title'] ?? '', 'Relance commerciale envoyée'));
    }

    public function test_unsubscribe_triggers_discord_event(): void
    {
        Http::fake(['discord.example/*' => Http::response('', 204)]);

        $audit = $this->makeAudit();
        $this->get('/audit/'.$audit->uuid.'/followup/unsubscribe?token='.$audit->followupUnsubscribeToken())
            ->assertOk();

        Http::assertSent(fn ($req) => str_contains($req->url(), 'discord.example')
            && str_contains($req->data()['embeds'][0]['title'] ?? '', 'Désinscription'));
    }
}
