# STRIPE_INTEGRATION.md — Module Audits (Laravel + Inertia + React)

> Remplace la version précédente. Adapté à un projet monolithe Laravel + Inertia.js + React.

---

## 0. Spécificité Inertia : le piège des redirections externes

Avec Inertia, quand React fait `router.post('/audits/premium/checkout')`, le contrôleur Laravel **ne peut pas** faire :
- `return redirect('https://checkout.stripe.com/...')` → Inertia interceptera la réponse et resterait sur la même page.
- `return response()->json([...])` → Inertia plante, il s'attend à une réponse Inertia.

**La bonne réponse, c'est `Inertia::location($url)`.** Ça envoie un header `X-Inertia-Location` qui force le client à faire un `window.location = ...` côté JS. C'est LE truc qui fait sortir d'Inertia pour aller sur Stripe.

Garde ça en tête, c'est la source numéro 1 de bugs sur ce genre d'intégration.

---

## 1. Architecture des routes

Trois groupes de routes, **séparés par leur middleware** :

```
┌─────────────────────────────────────────────────────────────┐
│ routes/web.php   — Middleware: web (session + CSRF + Inertia) │
│  • GET  /audits/premium                  (page de vente)      │
│  • POST /audits/premium/checkout         (création session)   │
│  • GET  /audits/merci                    (confirmation)       │
│  • GET  /audits/annule                   (annulation)         │
│  • GET  /r/{token}                       (lecture rapport)    │
├─────────────────────────────────────────────────────────────┤
│ routes/web.php — SANS middleware web (withoutMiddleware)      │
│  • POST /stripe/webhook                  (Stripe → toi)        │
└─────────────────────────────────────────────────────────────┘
```

Le webhook **doit être hors du middleware `web`**, sinon Laravel rejette parce que :
- pas de session ✗
- pas de CSRF token ✗
- Inertia n'aime pas non plus ✗

---

## 2. Exclure le webhook du CSRF

### Laravel 11+ (`bootstrap/app.php`)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'stripe/webhook',
    ]);
})
```

### Laravel 10 et antérieur (`app/Http/Middleware/VerifyCsrfToken.php`)

```php
protected $except = [
    'stripe/webhook',
];
```

---

## 3. Config & .env

### `.env`

```ini
STRIPE_KEY=pk_test_xxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx

AUDITS_PREMIUM_PRICE_CENTS=14900
AUDITS_PREMIUM_CURRENCY=eur
AUDITS_ADMIN_EMAIL=toi@tonsite.fr
```

### `config/services.php`

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

### `config/audits.php`

```php
<?php

return [
    'premium' => [
        'price_cents' => (int) env('AUDITS_PREMIUM_PRICE_CENTS', 14900),
        'currency' => env('AUDITS_PREMIUM_CURRENCY', 'eur'),
        'product_name' => 'Audit premium SEO + Sécurité',
        'session_expires_minutes' => 30,
    ],
    'report_token_ttl_days' => 90,
    'admin_email' => env('AUDITS_ADMIN_EMAIL', 'admin@example.com'),
];
```

---

## 4. Service Stripe

### `app/Modules/Audits/Services/StripeCheckoutService.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\Audits\Services;

use App\Modules\Audits\Models\AuditRequest;
use Stripe\StripeClient;
use Stripe\Checkout\Session;

class StripeCheckoutService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createSessionForAuditRequest(AuditRequest $auditRequest): Session
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],

            'line_items' => [[
                'price_data' => [
                    'currency' => config('audits.premium.currency'),
                    'product_data' => [
                        'name' => config('audits.premium.product_name'),
                        'description' => "Audit SEO + Sécurité pour {$auditRequest->domain} — livraison sous 24-48h",
                    ],
                    'unit_amount' => config('audits.premium.price_cents'),
                ],
                'quantity' => 1,
            ]],

            'customer_email' => $auditRequest->email,

            'success_url' => route('audits.merci') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('audits.annule'),

            'metadata' => [
                'audit_request_id' => (string) $auditRequest->id,
                'domain' => $auditRequest->domain,
            ],

            'expires_at' => now()->addMinutes(
                config('audits.premium.session_expires_minutes')
            )->timestamp,

            'locale' => 'fr',

            'invoice_creation' => [
                'enabled' => true,
            ],

            'billing_address_collection' => 'required',
        ]);

        $auditRequest->update([
            'stripe_session_id' => $session->id,
        ]);

        return $session;
    }
}
```

---

## 5. Form Request

### `app/Modules/Audits/Http/Requests/CreatePremiumOrderRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePremiumOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email:rfc,dns', 'max:180'],
            'domain'           => ['required', 'string', 'max:253', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'options'          => ['required', 'array'],
            'options.seo'      => ['boolean'],
            'options.security' => ['boolean'],
            'rgpd_consent'     => ['accepted'],
            'website'          => ['nullable', 'size:0'],   // honeypot
            'form_loaded_at'   => ['required', 'integer'],  // time-to-fill
        ];
    }

    public function messages(): array
    {
        return [
            'domain.regex' => 'Le domaine doit être au format "exemple.fr" (sans https://).',
            'rgpd_consent.accepted' => 'Vous devez accepter le traitement de vos données.',
        ];
    }

    public function passedValidation(): void
    {
        $domain = strtolower($this->input('domain'));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        $this->merge(['domain' => $domain]);
    }
}
```

---

## 6. Controller checkout — version Inertia

### `app/Modules/Audits/Http/Controllers/PremiumOrderController.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audits\Http\Requests\CreatePremiumOrderRequest;
use App\Modules\Audits\Models\AuditRequest;
use App\Modules\Audits\Services\StripeCheckoutService;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PremiumOrderController extends Controller
{
    /** Page de vente du premium. */
    public function show()
    {
        return Inertia::render('Audits/Premium', [
            'price_cents' => config('audits.premium.price_cents'),
            'currency'    => config('audits.premium.currency'),
        ]);
    }

    /** Création de la session Stripe + redirection externe via Inertia::location. */
    public function createCheckoutSession(
        CreatePremiumOrderRequest $request,
        StripeCheckoutService $stripe
    ) {
        // Anti-bot silencieux : honeypot ou form rempli trop vite
        $tooFast = (now()->timestamp - (int) $request->form_loaded_at) < 2;
        if ($request->filled('website') || $tooFast) {
            return redirect()->route('audits.merci');
        }

        $auditRequest = AuditRequest::create([
            'type'         => 'premium',
            'email'        => $request->email,
            'name'         => $request->name,
            'domain'       => $request->domain,
            'options'      => $request->options,
            'status'       => 'pending_payment',
            'amount_cents' => config('audits.premium.price_cents'),
            'currency'     => config('audits.premium.currency'),
            'access_token' => Str::random(64),
            'ip_address'   => $request->ip(),
            'user_agent'   => substr((string) $request->userAgent(), 0, 255),
        ]);

        try {
            $session = $stripe->createSessionForAuditRequest($auditRequest);
        } catch (\Throwable $e) {
            report($e);
            $auditRequest->update(['status' => 'failed']);

            return back()->withErrors([
                'stripe' => 'Impossible de créer la session de paiement, réessayez dans un instant.',
            ]);
        }

        // ⚠️ POINT CRITIQUE : sortie d'Inertia vers une URL externe
        return Inertia::location($session->url);
    }

    public function merci()
    {
        return Inertia::render('Audits/Merci');
    }

    public function annule()
    {
        return Inertia::render('Audits/Annule');
    }
}
```

---

## 7. Controller webhook

### `app/Modules/Audits/Http/Controllers/StripeWebhookController.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audits\Models\AuditRequest;
use App\Modules\Audits\Notifications\NewPremiumOrderForAdmin;
use App\Modules\Audits\Mail\PremiumOrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        // 1. Vérification de signature — sécurité critique
        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException $e) {
            Log::warning('Stripe webhook: payload invalide', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: signature invalide', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
                'checkout.session.expired'   => $this->handleCheckoutExpired($event->data->object),
                'charge.refunded'            => $this->handleChargeRefunded($event->data->object),
                default                      => Log::info("Stripe webhook ignoré: {$event->type}"),
            };
        } catch (\Throwable $e) {
            report($e);
            Log::error('Stripe webhook: échec de traitement', [
                'event' => $event->type,
                'error' => $e->getMessage(),
            ]);
            // 500 → Stripe retry (backoff exponentiel jusqu'à 3 jours)
            return response('Webhook processing failed', 500);
        }

        return response('OK', 200);
    }

    private function handleCheckoutCompleted(\Stripe\Checkout\Session $session): void
    {
        $auditRequestId = $session->metadata->audit_request_id ?? null;

        // ⚠️ Pas de metadata audit_request_id = paiement pour AUTRE CHOSE.
        // On l'ignore proprement, c'est pas notre business.
        if (!$auditRequestId) {
            Log::info('Stripe webhook: session sans audit_request_id, ignorée', [
                'session_id' => $session->id,
            ]);
            return;
        }

        DB::transaction(function () use ($session, $auditRequestId) {
            /** @var AuditRequest|null $audit */
            $audit = AuditRequest::lockForUpdate()->find($auditRequestId);

            if (!$audit) {
                Log::warning('Stripe webhook: AuditRequest introuvable', ['id' => $auditRequestId]);
                return;
            }

            // Idempotence : si déjà traité, on sort proprement
            if ($audit->status !== 'pending_payment') {
                Log::info('Stripe webhook: déjà traité, idempotent', [
                    'audit_id' => $audit->id,
                    'status'   => $audit->status,
                ]);
                return;
            }

            $audit->update([
                'status'                => 'queued',
                'stripe_payment_intent' => $session->payment_intent,
                'amount_cents'          => $session->amount_total,
                'currency'              => $session->currency,
                'paid_at'               => now(),
            ]);

            Mail::to($audit->email)->queue(new PremiumOrderConfirmation($audit));
            Notification::route('mail', config('audits.admin_email'))
                ->notify(new NewPremiumOrderForAdmin($audit));
        });
    }

    private function handleCheckoutExpired(\Stripe\Checkout\Session $session): void
    {
        $auditRequestId = $session->metadata->audit_request_id ?? null;
        if (!$auditRequestId) return;

        AuditRequest::where('id', $auditRequestId)
            ->where('status', 'pending_payment')
            ->update(['status' => 'failed']);
    }

    private function handleChargeRefunded(\Stripe\Charge $charge): void
    {
        $audit = AuditRequest::where('stripe_payment_intent', $charge->payment_intent)->first();
        if (!$audit) return;

        $audit->update(['status' => 'refunded']);
    }
}
```

---

## 8. Routes

### `routes/web.php`

```php
use App\Modules\Audits\Http\Controllers\PremiumOrderController;
use App\Modules\Audits\Http\Controllers\StripeWebhookController;

// === Module Audits — pages avec Inertia ===
Route::prefix('audits')->name('audits.')->group(function () {
    Route::get('/premium', [PremiumOrderController::class, 'show'])->name('premium');
    Route::post('/premium/checkout', [PremiumOrderController::class, 'createCheckoutSession'])
        ->middleware('throttle:5,1')
        ->name('premium.checkout');
    Route::get('/merci', [PremiumOrderController::class, 'merci'])->name('merci');
    Route::get('/annule', [PremiumOrderController::class, 'annule'])->name('annule');
});

// === Webhook Stripe — HORS middleware web (pas de session, pas de CSRF) ===
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['web'])
    ->name('stripe.webhook');
```

---

## 9. Côté React — composant Inertia

### `resources/js/Pages/Audits/Premium.tsx`

```tsx
import { useRef } from 'react';
import { useForm } from '@inertiajs/react';

interface Props {
    price_cents: number;
    currency: string;
}

export default function Premium({ price_cents, currency }: Props) {
    const formLoadedAt = useRef(Math.floor(Date.now() / 1000));

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        domain: '',
        options: { seo: true, security: true },
        rgpd_consent: false,
        website: '',                          // honeypot
        form_loaded_at: formLoadedAt.current,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // ⚠️ Pas de onSuccess ici : la redirection vers Stripe se fait automatiquement
        // grâce à Inertia::location() côté serveur. Inertia détecte le header
        // X-Inertia-Location et fait window.location = url tout seul.
        post(route('audits.premium.checkout'));
    };

    const priceFormatted = (price_cents / 100).toLocaleString('fr-FR', {
        style: 'currency',
        currency: currency.toUpperCase(),
    });

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <h1>Audit premium — {priceFormatted}</h1>

            <input
                type="text"
                placeholder="Nom"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
            />
            {errors.name && <p className="error">{errors.name}</p>}

            <input
                type="email"
                placeholder="Email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
            />
            {errors.email && <p className="error">{errors.email}</p>}

            <input
                type="text"
                placeholder="exemple.fr"
                value={data.domain}
                onChange={(e) => setData('domain', e.target.value)}
            />
            {errors.domain && <p className="error">{errors.domain}</p>}

            <label>
                <input
                    type="checkbox"
                    checked={data.options.seo}
                    onChange={(e) =>
                        setData('options', { ...data.options, seo: e.target.checked })
                    }
                />
                Audit SEO
            </label>

            <label>
                <input
                    type="checkbox"
                    checked={data.options.security}
                    onChange={(e) =>
                        setData('options', { ...data.options, security: e.target.checked })
                    }
                />
                Audit sécurité
            </label>

            <label>
                <input
                    type="checkbox"
                    checked={data.rgpd_consent}
                    onChange={(e) => setData('rgpd_consent', e.target.checked)}
                />
                J'accepte le traitement de mes données
            </label>
            {errors.rgpd_consent && <p className="error">{errors.rgpd_consent}</p>}

            {/* HONEYPOT — caché en CSS, doit rester vide */}
            <input
                type="text"
                name="website"
                value={data.website}
                onChange={(e) => setData('website', e.target.value)}
                tabIndex={-1}
                autoComplete="off"
                style={{ position: 'absolute', left: '-9999px' }}
                aria-hidden="true"
            />

            {errors.stripe && <p className="error">{errors.stripe}</p>}

            <button type="submit" disabled={processing}>
                {processing ? 'Redirection vers Stripe...' : `Payer ${priceFormatted}`}
            </button>
        </form>
    );
}
```

### `resources/js/Pages/Audits/Merci.tsx`

```tsx
export default function Merci() {
    return (
        <div className="text-center py-16">
            <h1>Merci pour votre commande</h1>
            <p>
                Vous allez recevoir un email de confirmation dans quelques instants.
                Votre rapport sera livré sous 24 à 48h.
            </p>
        </div>
    );
}
```

### `resources/js/Pages/Audits/Annule.tsx`

```tsx
import { Link } from '@inertiajs/react';

export default function Annule() {
    return (
        <div className="text-center py-16">
            <h1>Paiement annulé</h1>
            <p>Aucun montant n'a été prélevé.</p>
            <Link href={route('audits.premium')}>Retour au formulaire</Link>
        </div>
    );
}
```

---

## 10. Tester en local avec Stripe CLI

### Installation
```bash
brew install stripe/stripe-cli/stripe
# autres OS : https://docs.stripe.com/stripe-cli
```

### Setup
```bash
stripe login
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

→ ça affiche un `whsec_xxx` temporaire → mets-le dans `.env` comme `STRIPE_WEBHOOK_SECRET` pour la durée des tests.

### Test E2E
1. Terminal 1 : `php artisan serve`
2. Terminal 2 : `npm run dev`
3. Terminal 3 : `stripe listen --forward-to http://localhost:8000/stripe/webhook`
4. Terminal 4 : `php artisan queue:work` (pour que les mails partent)
5. Va sur `http://localhost:8000/audits/premium`
6. Remplis le form, paie avec `4242 4242 4242 4242` / date future / CVC quelconque
7. Vérifie : redirection vers `/audits/merci`, ligne en base passée à `queued`, email reçu en local (Mailpit / log).

---

## 11. Configurer le webhook en production

1. https://dashboard.stripe.com/webhooks → "Add endpoint"
2. URL : `https://tonsite.fr/stripe/webhook`
3. Events à écouter (sélection précise, pas "tous") :
   - `checkout.session.completed`
   - `checkout.session.expired`
   - `charge.refunded`
4. Récupère le `whsec_xxx` → `.env` de prod (différent du CLI et de tes clés API).

---

## 12. Pièges spécifiques Inertia

1. **`Inertia::location()` est obligatoire pour Stripe.** `return redirect($url)` ne marche PAS depuis une requête Inertia : ça reste sur la même page.

2. **`return response()->json(...)` casse Inertia.** Jamais depuis un controller appelé via `router.post()` ou `useForm().post()`.

3. **Erreurs de validation = automatiques avec `useForm`.** Pas besoin de gérer le 422 manuellement, `errors.xxx` est rempli côté React.

4. **Le webhook n'est PAS Inertia.** Il répond du texte brut à Stripe → d'où le `withoutMiddleware(['web'])`.

5. **CSRF token automatique avec Inertia.** Sauf pour le webhook, qui doit en être exclu.

6. **`route()` côté React** : disponible si tu as `ziggy-js` (souvent inclus avec Breeze/Jetstream). Sinon, hardcode `'/audits/premium/checkout'`.

7. **Bouton submit `disabled` pendant `processing`**, sinon double-soumission = deux `AuditRequest` créés (Stripe est idempotent côté lui, pas toi).

---

## 13. Checklist avant prod

- [ ] `composer require stripe/stripe-php` si pas déjà installé.
- [ ] `bootstrap/app.php` (L11) ou `VerifyCsrfToken::$except` (L10) à jour avec `stripe/webhook`.
- [ ] Clés `live` dans `.env` de prod, pas `test`.
- [ ] Webhook configuré sur dashboard Stripe live.
- [ ] `STRIPE_WEBHOOK_SECRET` du mode live (différent du CLI).
- [ ] Throttle 5/min sur `audits.premium.checkout`.
- [ ] Queue worker actif en prod (`php artisan queue:work` via supervisor ou Horizon).
- [ ] Test E2E avec une vraie carte (à se rembourser après).
- [ ] Sentry/Bugsnag branché pour capturer les exceptions du webhook.
- [ ] CGV avec clause "vous certifiez être propriétaire du domaine".
- [ ] Mentions RGPD dans le formulaire.

---

## 14. Si tu finis par avoir Cashier

Si `composer show | grep stripe` te montre `laravel/cashier`, deux options :

**A. Ignorer Cashier pour ce module** (simple, recommandé)
Cashier sert surtout aux **abonnements** avec un `Billable` model. Pour un paiement one-shot lié à un domaine sans compte utilisateur, c'est plus propre d'utiliser `stripe/stripe-php` directement comme dans ce doc.

**B. Étendre le `WebhookController` de Cashier**
Si tu veux centraliser tous tes webhooks Stripe, étends `Laravel\Cashier\Http\Controllers\WebhookController` et ajoute une méthode `handleCheckoutSessionCompleted` (nom magique reconnu par Cashier). Il route automatiquement les events vers la bonne méthode.

À faire **uniquement si** tu as déjà du Cashier qui répond à ses propres webhooks en prod. Sinon = complexité gratuite.