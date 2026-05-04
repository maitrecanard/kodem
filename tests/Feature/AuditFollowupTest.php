<?php

namespace Tests\Feature;

use App\Mail\AuditFollowupMail;
use App\Models\Audit;
use App\Models\AuditFollowup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuditFollowupTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAudit(array $overrides = []): Audit
    {
        $createdAt = $overrides['created_at'] ?? now()->subDays(8);
        unset($overrides['created_at'], $overrides['updated_at']);

        $audit = Audit::create(array_merge([
            'url' => 'https://low.example',
            'email' => 'lead@exemple.fr',
            'type' => 'full',
            'status' => 'completed',
            'score_seo' => 50,
            'score_security' => 40,
            'score_total' => 45,
        ], $overrides));

        // Eloquent overwrite created_at/updated_at on insert ; on force a posteriori.
        Audit::query()->where('id', $audit->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $audit->fresh();
    }

    public function test_command_sends_followup_for_low_score_audit_after_one_week(): void
    {
        Mail::fake();

        $audit = $this->makeAudit();

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertSent(AuditFollowupMail::class, fn ($mail) => $mail->hasTo('lead@exemple.fr'));

        $this->assertDatabaseHas('audit_followups', [
            'audit_id' => $audit->id,
            'email' => 'lead@exemple.fr',
            'reason' => AuditFollowup::REASON_LOW_SCORE,
            'status' => AuditFollowup::STATUS_SENT,
            'score_at_send' => 45,
        ]);
    }

    public function test_command_skips_audit_above_threshold(): void
    {
        Mail::fake();

        $this->makeAudit(['score_total' => 80, 'score_seo' => 80, 'score_security' => 80]);

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('audit_followups', 0);
    }

    public function test_command_skips_audit_younger_than_delay(): void
    {
        Mail::fake();

        $this->makeAudit([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_command_skips_audit_older_than_max_age(): void
    {
        Mail::fake();

        $this->makeAudit([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ]);

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_command_is_idempotent(): void
    {
        Mail::fake();

        $audit = $this->makeAudit();

        $this->artisan('audits:send-followup')->assertExitCode(0);
        $this->artisan('audits:send-followup')->assertExitCode(0);

        $this->assertSame(1, $audit->followups()->count());
        Mail::assertSent(AuditFollowupMail::class, 1);
    }

    public function test_command_skips_unsubscribed_audit(): void
    {
        Mail::fake();

        $this->makeAudit(['followup_unsubscribed_at' => now()->subDay()]);

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_command_skips_audit_without_email(): void
    {
        Mail::fake();

        $this->makeAudit(['email' => null]);

        $this->artisan('audits:send-followup')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_unsubscribe_route_marks_audit_and_rejects_bad_token(): void
    {
        $audit = $this->makeAudit();

        $this->get('/audit/'.$audit->uuid.'/followup/unsubscribe?token=wrong')
            ->assertForbidden();

        $audit->refresh();
        $this->assertNull($audit->followup_unsubscribed_at);

        $this->get('/audit/'.$audit->uuid.'/followup/unsubscribe?token='.$audit->followupUnsubscribeToken())
            ->assertOk();

        $audit->refresh();
        $this->assertNotNull($audit->followup_unsubscribed_at);
    }

    public function test_followup_relation_is_attached_to_the_contact_audit(): void
    {
        Mail::fake();

        $audit = $this->makeAudit();
        $this->artisan('audits:send-followup')->assertExitCode(0);

        $followup = $audit->followups()->first();
        $this->assertNotNull($followup);
        $this->assertSame($audit->id, $followup->audit_id);
        $this->assertSame($audit->email, $followup->email);
        $this->assertTrue($audit->hasFollowupBeenSent());
    }

    public function test_dry_run_does_not_send_or_persist(): void
    {
        Mail::fake();

        $this->makeAudit();

        $this->artisan('audits:send-followup', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('audit_followups', 0);
    }
}
