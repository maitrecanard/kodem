# APEX Project Memory

## Stack & Tooling
- PHP 8.2 + Laravel 11.31 (bootstrap/app.php, no Kernel.php). Inertia.js 2.0 + React 18.2 (JSX only, NO TypeScript). Vite 6 (client + SSR). Tailwind 3.2.
- Test command: `php artisan test` (optionally `--filter=...` or `tests/Feature/X.php`). Test DB is isolated in-memory sqlite via phpunit.xml (APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:) — never touches dev/prod.
- Build: `npm run build` (client + SSR). Dev: `npm run dev` / `composer dev`.
- Baseline: ~178 passing / 7 PRE-EXISTING failures unrelated to any vitrine work (AuditCwv, AuditPdf, DiscordNotifier, Monitoring, ServerSideEvents x2, TrustProxies — all payment-stub / external-event / proxy infra). Do not chase these.

## Architecture
- Monolith with TWO public surfaces coexisting:
  1. Audit product (SEO/security audits, monitoring, payments) — original site.
  2. Vitrine "dispositifs logiciels connectés sur site" (photomatons, écrans Raspberry, signalétique) — added 2026-06-30, ADDITIVE.
- Inertia flow: `Inertia::render('Public/PageName', ['meta'=>['title','description'], ...props])`. PublicLayout.jsx consumes meta.title/description only (omit `keywords` on new pages — charter §8 forbids keyword stuffing).
- Vitrine content is FILE-BASED (no DB/Eloquent): `content/*.json` loaded by `App\Services\VitrineContent` (static, mirrors `App\Services\PrestationCatalog`).

## Conventions
- React: JSX functional components, Tailwind className, semantic HTML, aria attributes. No TSX anywhere.
- Routes: snake-case (/hebergement-web, /zone-intervention). Controllers/Components: PascalCase.
- Tailwind tokens (charter §2, already in tailwind.config.js): cobalt #2348E0 (accent only), encre #0B1B4D (indigo, dark/text), brume #E8ECFF, acier #64748B, papier #FBFCFE (dominant bg). Fonts: Space Grotesk (sans), JetBrains Mono (mono). 8pt grid (max-w-6xl, px-6). CSS utils .kodem-diagonal, .kodem-eyebrow.
- Reusable components: Banner, SectionLabel, CodeButton, DiagonalPattern, ContactBlockMono, BrandWordmark, CaseStudy, Testimonial(+TestimonialSection).
- JSON-LD built server-side in resources/views/app.blade.php as a `$graph`, encoded ONCE with hardened flags `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT` (+UNESCAPED_SLASHES/UNICODE). Per-page nodes merged via `data_get($page,'props.jsonLd',[])` BEFORE encode. Pages pass a `jsonLd` array prop (BreadcrumbList; FAQPage where a FAQ is rendered).

## Critical Files
- `app/Http/Controllers/PublicController.php` — all public page actions + meta + jsonLd.
- `app/Services/VitrineContent.php` — loads content/*.json (positioning/cases/testimonials); `case($slug)`/`testimonialsForCase($slug)` are in-memory array lookups (no FS path from slug).
- `content/positioning.json` — SINGLE SOURCE of niche positioning copy (charter §0, POSITIONNEMENT_VALIDÉ=false → copy lives ONLY here). To switch to generic "4 expertises" mode: flip the flag and replace this file's content.
- `content/cases.json` — case studies (photomaton, ecran-raspberry); every real figure is a `// TODO:` sentinel (never invent numbers — §6).
- `content/testimonials.json` — currently `[]`. Shape: {citation, nom, fonction, structure, photo?, cas_lie?}. When adding real ones, set `cas_lie` to the CASE SLUG (photomaton/ecran-raspberry) — that's what `testimonialsForCase` matches on (cases.json's `testimonial_slug` field is a separate, unused-by-the-filter convention).
- `resources/views/app.blade.php` — head + global JSON-LD (Organization/WebSite/Service/LocalBusiness). LocalBusiness NAP/geo are `// TODO` placeholders.
- `resources/js/Layouts/PublicLayout.jsx` — header nav + footer; vitrine links added without touching audit links/CTA/`dev · host · seo · sec` signature.
- `app/Http/Controllers/SitemapController.php` — sitemap incl. vitrine URLs + per-case loop.
- `kodem/apex-endpoints.json` — incremental endpoint test manifest (5 vitrine endpoints, all PASS).

## Known Constraints
- Charter §8 anti-goals: do NOT reintroduce "4 expertises en façade" while POSITIONNEMENT_VALIDÉ=false; the four (dev/host/seo/sécurité) are framed as SUPPORT capabilities only (Expertises.jsx, positioning.json `capabilities[].role_support`). No generic volume content. Don't degrade the charter visuals.
- Testimonials: never anonymous, never invented, always chiffré; hide section entirely when no real data. NEVER emit Review/AggregateRating schema (Google forbids self-reviews — §4).
- All real §6 data (case figures, NAP address/phone/geo, testimonials, case images WebP<300ko, dates) remain `// TODO` and are BLOCKING for production go-live, not implementation defects. Inventory: `grep -rn "// TODO" content/ resources/js/Pages/Public resources/js/Components app/Services resources/views/app.blade.php`.

## Testing
- Unit: tests/Unit/ (e.g. VitrineContentTest.php — service logic incl. path-traversal-safe slug lookups).
- Feature/E2E: tests/Feature/ using `Inertia\Testing\AssertableInertia as Assert`, `$this->get()`, RefreshDatabase. Public routes are guest-accessible (no auth). VitrineTest.php covers all 5 vitrine endpoints (200, meta, BreadcrumbList jsonLd, no Review/AggregateRating in HTML, testimonials hidden when empty, 404 on unknown slug, audit-route regression).
- AssertableInertia note: a `->where('prop', fn ($x) => ...)` callback may receive an Illuminate\Support\Collection, not a bare array — don't type-hint `array` in the closure.

## Security Notes
- JSON-LD XSS-safe: single hardened json_encode on the FINAL merged graph; never merge a node after encoding, never a second un-flagged encode.
- Slug params are opaque content strings used only for in-memory `firstWhere` lookups — never concatenated into a filesystem path. VitrineContent reads fixed `base_path('content/xxx.json')` literals only.
- Global rule: any user identifier in the frontend MUST be UUID/opaque, never a raw DB id. Vitrine exposes only content slugs (photomaton/ecran-raspberry). Existing entity routes already use {audit:uuid}/{subscription:token}.
- No secrets/PII in content/*.json or new code.

## Past APEX Tasks
- [2026-06-30] Execute claude.md §9 — built additive KODEM vitrine (positioning source + cases + testimonials components, /realisations[/{slug}], /expertises, /zone-intervention, /notes, LocalBusiness+BreadcrumbList JSON-LD, sitemap, nav/footer). Files: app/Services/VitrineContent.php, content/{positioning,cases,testimonials}.json, resources/js/Components/{CaseStudy,Testimonial}.jsx, resources/js/Pages/Public/{Realisations,RealisationShow,Expertises,ZoneIntervention,Notes}.jsx, + edits to PublicController/routes/app.blade.php/PublicLayout/SitemapController. Tests: 8 unit + 11 feature (all PASS). Audit: SECURE.
