# APEX Project Memory

## Stack & Tooling
- **PHP** ^8.2, **Laravel** ^11.31 (bootstrap/app.php — pas Kernel.php).
- **Inertia.js** 2.0 + **React** 18.2 (TOUTES les pages sont en `.jsx`, PAS de TypeScript : pas de tsconfig.json, et le resolveur Inertia hardcode `.jsx` dans `resources/js/app.jsx` et `resources/js/ssr.jsx`).
- **Stripe PHP** ^20.1 installé.
- **Ziggy** 2.0 → `route()` disponible globalement côté JS (pas besoin d'import).
- **Tests** : PHPUnit 11 — config `phpunit.xml` (SQLite `:memory:`, MAIL_MAILER=array, QUEUE=sync).
- Commande tests : `php artisan test` (full), `php artisan test --testsuite=Unit|Feature`, `php artisan test --filter=<pattern>`.
- Build : `npm run build` (Vite, client + SSR).

## Architecture
- Projet "kodem" : Laravel monolithe avec Inertia + React.
- Structure majoritairement **flat** sous `app/` (Controllers, Models, Services, Mail à la racine), MAIS le module Audits Premium est sous `app/Modules/Audits/` (premier module isolé, PSR-4 fonctionne via `App\` déjà mappé sur `app/` dans `composer.json`).
- Coexistence : `app/Models/Audit.php` (freemium) ≠ `app/Modules/Audits/Models/AuditRequest.php` (premium one-shot).
- `config/audit.php` (singulier, freemium) ≠ `config/audits.php` (pluriel, premium).
- Routes : freemium = `/audit/*` + nom `audit.*` ; premium = `/audits/*` + nom `audits.*` ; webhook Stripe = `/stripe/webhook` hors middleware `web`.

## Design System (charte KODEM)
- **Composants charte réutilisables** (`resources/js/Components/`, tous statiques SSR-safe, .jsx) : `SectionLabel` (étiquette mono `// X` + `number` optionnel), `CodeButton` (style `demander_audit()`, fond encre/mono blanc, rend `<Link>` si `href` sinon `<button>`), `DiagonalPattern` (wrapper `.kodem-diagonal`), `Banner` (bannière web FIDÈLE charte p.10 : PLEINE LARGEUR (full-bleed) sous la nav, hauteur `min-h-[300px] md:min-h-[400px]` (esprit 18:5, 400px charte), contenu aligné sur conteneur central `max-w-6xl`, `bg-encre` + DiagonalPattern réserve d'image + voile `bg-gradient-to-b from-encre/55 to-encre/15`, titre Display blanc + CTA primaire ancrés bas-gauche. API minimale `{title, cta, className}` — PAS de subtitle/tags/children : ce contenu va dans une section SOUS la bannière. Tags `dev · host · seo · sec` placés dans la nav globale `PublicLayout`, pas dans la bannière. Titres bannière courts façon charte « Concevoir, héberger, sécuriser. »), `ContactBlockMono` (fond encre, BrandWordmark blanc, contact mono + flèches `→`, défauts charte kodem.fr/contact@kodem.fr — placeholders tél/adresse à renseigner avant prod).
- Tokens/typo dans `tailwind.config.js` : `text-display`(56), `text-kodem-h1`(34), `text-kodem-h2`(24), `text-legende`(11), `rounded-kodem`(10px), rampe `cobalt` (600 primaire, 950 encre), `indigo-*` remappé cobalt. Utilitaires CSS `.kodem-diagonal` + `.kodem-eyebrow` dans `resources/css/app.css`.

## SEO
- **Meta par page** : controller fait `Inertia::render('Page', ['meta' => ['title','description','keywords']])` → `PublicLayout.jsx` rend `<Head>` title/description/canonical/OG/Twitter. Nouvelle page publique = controller + route + page sous `Pages/Public/` + passer `meta`.
- **JSON-LD** rendu côté serveur dans `resources/views/app.blade.php` (@graph : Organization + WebSite + 1 `Service` par prestation via `PrestationCatalog::all()`, `offers` omis si `price_from` null = « Sur devis »). `json_encode` durci avec `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`.
- **Sitemap** : route dynamique `/sitemap.xml` (`SitemapController@index` → `resources/views/sitemap.blade.php`), 8 URLs publiques (jamais token/uuid/admin). `public/robots.txt` déclare `Sitemap: https://kodem.fr/sitemap.xml` (domaine charte ; APP_URL dev = localhost:8000).
- **Catalogue** : `app/Services/PrestationCatalog.php` (source de vérité, 7 prestations) — champs réels : `slug,title,price_from,price_label,tagline,description,features[],cta,cta_route`. `::all()` = 7, `::teaser()` = 4 premières (Home). Landing dédiée : `/hebergement-web` (prestation `hebergement-web`).

## Conventions
- **Pages React en `.jsx` uniquement** (jamais `.tsx`, le resolveur Inertia ne les voit pas).
- **Mailable Blade view** (pas markdown), pattern : voir `app/Mail/AuditFollowupMail.php` et `resources/views/emails/audit_followup.blade.php`.
- **Services** : injection par constructeur via container Laravel (pas de méthodes statiques).
- **Form Requests** : `passedValidation()` pour normaliser (lowercase, strip scheme, etc.).
- **Inertia + Stripe** : `return Inertia::location($url)` est l'UNIQUE moyen de rediriger vers une URL externe depuis un controller Inertia. JAMAIS `redirect($url)` (Inertia ignore) ni `response()->json([...])` (Inertia plante).
- **Webhook Stripe** : `Route::post(...)->withoutMiddleware(['web'])` + exception CSRF dans `bootstrap/app.php` (`$middleware->validateCsrfTokens(except: ['stripe/webhook'])`).
- **Idempotence webhook** : `DB::transaction` + `lockForUpdate()` + check status avant update. Pour TOUS les handlers (completed, expired, refunded), pas juste completed.

## Critical Files
- `bootstrap/app.php` — middleware injection + exception CSRF pour `stripe/webhook`.
- `config/services.php` — bloc `stripe` (key, secret, webhook_secret) déjà OK.
- `routes/web.php` — routes audits.* + webhook ; routes freemium audit.* coexistent.
- `resources/js/app.jsx` + `ssr.jsx` — hardcode `.jsx`, ne pas tenter d'introduire TS sans modifier ces fichiers.
- `composer.json` — PSR-4 `"App\\": "app/"` → couvre `App\Modules\*` automatiquement après `composer dump-autoload`.

## Known Constraints
- **Ne JAMAIS exposer l'`id` auto-increment des modèles utilisateurs côté frontend.** AuditRequest a `getRouteKeyName='uuid'` et `$hidden=['id', 'access_token', 'stripe_session_id', 'stripe_payment_intent', 'ip_address', 'user_agent']`. Pour les logs, préférer `audit_uuid` à `audit_id`.
- **Pas de tsx** dans `resources/js/Pages/**` — utiliser `.jsx`.
- Ne pas casser la structure freemium existante (`AuditPaymentController`, `Audit` model, `config/audit.php`).
- Cashier n'est PAS installé et n'est PAS nécessaire (paiements one-shot, pas d'abonnement).

## Past APEX Tasks
- [2026-06-26] Refonte graphique charte (cœur fort impact) + SEO on-page (6 mots-clés) — files: NEW composants charte `resources/js/Components/{SectionLabel,CodeButton,DiagonalPattern,Banner,ContactBlockMono}.jsx`, NEW `resources/js/Pages/Public/Hebergement.jsx`, NEW `app/Http/Controllers/SitemapController.php` + `resources/views/sitemap.blade.php`, edits `Pages/Public/{Home,Services,Audit,Contact,AuditResult}.jsx` + `Layouts/PublicLayout.jsx` (Banner héros + SectionLabel mono + ContactBlockMono footer), `app/Http/Controllers/PublicController.php` (meta home/services enrichies + méthode `hebergement()`), `resources/views/app.blade.php` (nœuds JSON-LD `Service` par prestation), `routes/web.php` (routes `hebergement` + `sitemap`), `public/robots.txt` (directive Sitemap). tests: 7 Feature added (`tests/Feature/Seo/SeoRefonteTest.php`, 65 assertions, vertes) + fix assertion obsolète `PublicPagesTest::test_home_page_renders_with_seo_meta` (keywords changés). Suite : 159 passed / 7 failed (les 7 = legacy pré-existants non liés, voir ci-dessous). Security: SECURE (durcissement JSON-LD `JSON_HEX_*` ajouté, aucun id brut exposé, sitemap/JSON-LD = données statiques du PrestationCatalog).
- [2026-06-15] Route lecture rapport premium `GET /r/{token}` (capability URL) — seule pièce manquante du spec `claude.md`. files: NEW `app/Modules/Audits/Http/Controllers/AuditReportController.php`, NEW `resources/js/Pages/Audits/Report.jsx`, edits `app/Modules/Audits/Models/AuditRequest.php` (scopeByAccessToken + isReportAccessible), `routes/web.php` (route nommée `audits.report`, regex `[A-Za-z0-9]{64}`, `throttle:30,1`), `resources/views/emails/premium_order_confirmation.blade.php` (lien rapport). tests: 24 added (8 Feature report + 10 Feature webhook + 6 Unit model) — full Audits suite 40 green / 151 assertions. Security: SECURE (no-store + noindex headers ajoutés sur la réponse, raw id JAMAIS exposé). Reste: 6 échecs legacy pré-existants non liés (AuditCwv/AuditPdf/Monitoring/ServerSideEvents/Discord — appels Stripe/stub réels HTTP 501/500).
- [2026-05-25] Module Audits Premium + Stripe Checkout (Laravel 11 + Inertia + React) — files: `app/Modules/Audits/**` (7 fichiers PHP), `config/audits.php`, `database/migrations/2026_05_25_120000_create_audit_requests_table.php`, `resources/views/emails/premium_order_confirmation.blade.php`, `resources/js/Pages/Audits/{Premium,Merci,Annule}.jsx`, edits dans `routes/web.php`, `bootstrap/app.php`, `.env.example`, `.env` — tests: 16 added (11 Feature + 5 Unit, 72 assertions, all green; 6 pre-existing failures sur tests legacy, non liés).

## Security Notes
- **Webhook Stripe** : `Webhook::constructEvent` obligatoire (vérification signature). En cas de payload invalide → 400, échec de traitement → 500 (Stripe retry exponentiel jusqu'à 3 jours).
- **Honeypot + time-to-fill** : pattern anti-bot retenu : champ `website` (`size:0`) + `form_loaded_at` (avec `min:` = `now()->subDay()->timestamp`, `max:` = `now()->timestamp`). Threshold time-to-fill = 3 secondes. Rejet via `back()->withErrors([...])` + `Log::warning` — NE PAS rediriger vers la page de succès (silently dropping legitimate users).
- **Whitelist des clés** dans champs `array` du Form Request : utiliser `array:key1,key2` (Laravel 9+) pour éviter la pollution JSON.
- **PII** : `ip_address` + `user_agent` stockés pour antifraude → ajouter une rétention/purge (~90 jours) en prod. Documenter dans la privacy policy.
- **STRIPE_WEBHOOK_SECRET** vide/null → `Webhook::constructEvent` rejette avec `SignatureVerificationException` (testé). Bon par défaut.
- **Capability URL `/r/{token}`** : le `access_token` (64 chars, `Str::random` ≈ 381 bits) EST l'authentification. Lookup direct indexé (`scopeByAccessToken`), `abort(404)` UNIFORME pour tous les cas d'échec (token inconnu / non payé / expiré) → aucune fuite d'info distinguant les cas. TTL calculé depuis `paid_at + report_token_ttl_days` via `AuditRequest::isReportAccessible()` (PAS de colonne expires_at). Réponse durcie : headers `Cache-Control: no-store` + `X-Robots-Tag: noindex` + `throttle:30,1`. Props Inertia = whitelist manuelle (domain/type/options/status/paidAt/amount/currency) — jamais `toArray()`, jamais id/access_token/stripe_*/ip/user_agent/email/name/uuid.
- **`Inertia::render(...)->toResponse($request)->withHeaders([...])`** : pattern pour attacher des headers HTTP à une page Inertia tout en gardant `assertInertia()`/`assertOk()` fonctionnels (testé).
- **Test webhook signé** : `config(['services.stripe.webhook_secret' => 'whsec_test'])` + header `Stripe-Signature: t={ts},v1={hmac}` où hmac = `hash_hmac('sha256', "{ts}.{payload}", $secret)`. Pas de vrai secret dans les tests.

## Testing
- **Baseline d'échecs pré-existants (non liés à la charte/SEO)** : 7 tests rouges en env de test, indépendants des features récentes — `AuditCwvTest`/`AuditPdfTest` (stub payment → PageSpeed/PDF HTTP 501), `DiscordNotifierTest`, `MonitoringTest`, `ServerSideEventsTest` (×2) = appels Stripe/stub réels ; `TrustProxiesTest` (forwarded headers en dev). Suite de référence ≈ 159 passed / 7 failed. Ne PAS les attribuer à un changement présentationnel/SEO.
- Framework : PHPUnit 11.
- Commande : `php artisan test [--testsuite=Unit|Feature] [--filter=...]`.
- Convention : `tests/Feature/<Module>/<Class>Test.php`, `tests/Unit/<Module>/<Class>Test.php`. Utilise `RefreshDatabase`, `Mail::fake()`, `Notification::fake()`.
- **Inertia::location() renvoie 302 pour requête non-Inertia et 409 (avec header `X-Inertia-Location`) pour requête Inertia** (`X-Inertia: true`). Tester avec `withHeaders(['X-Inertia' => 'true'])`.
- **`email:rfc,dns`** dans validation → l'email doit avoir un MX réel pour passer. `example.com` n'a PAS de MX, utiliser `gmail.com` dans les tests.
- Mock service Stripe : `$this->createMock(StripeCheckoutService::class)` + `$this->app->instance(StripeCheckoutService::class, $mock)`. Pour stubber `Stripe\Checkout\Session`, utiliser `Session::constructFrom([...])` (PHPUnit refuse `stdClass` à cause du return type strict).
