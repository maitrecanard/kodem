<?php

namespace Tests\Feature;

use App\Mail\ContactAcknowledgement;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    // -------------------------------------------------------------------------
    // Envoi SMTP — notification interne + accusé de réception
    // -------------------------------------------------------------------------

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Alice Dupont',
            'email' => 'alice@exemple.fr',
            'company' => 'Société X',
            'subject' => 'Demande de devis SaaS',
            'message' => 'Bonjour, je souhaite un devis pour un dispositif connecté.',
        ], $overrides);
    }

    public function test_it_notifies_the_configured_address_and_acknowledges_the_visitor(): void
    {
        Mail::fake();
        config(['contact.notify_email' => 'contact@kodem.test', 'contact.notify_cc' => null]);

        $this->post('/contact', $this->validPayload())->assertRedirect();

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('contact@kodem.test'));
        Mail::assertSent(ContactAcknowledgement::class, fn ($mail) => $mail->hasTo('alice@exemple.fr'));
    }

    public function test_internal_notification_replies_to_the_visitor_not_from_them(): void
    {
        Mail::fake();
        config(['contact.notify_email' => 'contact@kodem.test']);

        $this->post('/contact', $this->validPayload())->assertRedirect();

        // Le Reply-To porte l'adresse du visiteur (réponse en un clic) mais le
        // From reste le domaine du site : mettre le visiteur en From casserait
        // SPF/DKIM et enverrait la notification en spam.
        Mail::assertSent(ContactMessageReceived::class, function ($mail) {
            $mail->assertHasReplyTo('alice@exemple.fr');

            // Aucun From propre au mail : c'est le mail.from global (domaine du
            // site, couvert par SPF/DKIM) qui s'applique. Le jour où quelqu'un
            // y mettrait l'adresse du visiteur, ce test échoue.
            $this->assertNull(
                $mail->envelope()->from,
                'le From doit rester celui du domaine, jamais celui du visiteur'
            );

            return true;
        });
    }

    public function test_acknowledgement_can_be_disabled_by_configuration(): void
    {
        Mail::fake();
        config(['contact.notify_email' => 'contact@kodem.test', 'contact.send_acknowledgement' => false]);

        $this->post('/contact', $this->validPayload())->assertRedirect();

        Mail::assertSent(ContactMessageReceived::class);
        Mail::assertNotSent(ContactAcknowledgement::class);
    }

    public function test_a_cc_address_is_added_when_configured(): void
    {
        Mail::fake();
        config(['contact.notify_email' => 'contact@kodem.test', 'contact.notify_cc' => 'associe@kodem.test']);

        $this->post('/contact', $this->validPayload())->assertRedirect();

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasCc('associe@kodem.test'));
    }

    public function test_an_smtp_failure_never_loses_the_message_and_is_logged(): void
    {
        config(['contact.notify_email' => 'contact@kodem.test']);

        // Simule un SMTP injoignable : Mail::send lève, le contrôleur doit
        // journaliser sans planter ni perdre la demande déjà persistée.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));
        Log::shouldReceive('error')->atLeast()->once();

        $this->post('/contact', $this->validPayload())->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'alice@exemple.fr',
            'status' => 'new',
        ]);
    }

    public function test_a_missing_notification_address_is_logged_and_does_not_break_submission(): void
    {
        Mail::fake();
        config(['contact.notify_email' => null, 'contact.send_acknowledgement' => false]);
        Log::shouldReceive('warning')->once();

        $this->post('/contact', $this->validPayload())->assertRedirect();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_spam_caught_by_the_honeypot_triggers_no_mail(): void
    {
        Mail::fake();

        $this->post('/contact', $this->validPayload(['website' => 'http://spam.ru']))->assertRedirect();

        Mail::assertNothingSent();
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
