# Kodem

Kodem est une société fictive de développement web, d'hébergement et d'audit (SEO + sécurité). Ce dépôt contient l'implémentation de son site institutionnel et de sa plateforme d'audit automatisé en libre-service.

Stack monolithique : **Laravel 11** (PHP 8.3) + **Inertia.js** + **React 18** + **Vite** + **SSR**, base de données **MySQL** en production (SQLite par défaut en développement et en tests).

---

## 1. Cahier des charges initial et traçabilité

Le README d'origine demandait :

| # | Fonctionnalité demandée | Statut | Implémentation |
|---|---|---|---|
| 1 | Description du projet : société de dév logiciel, hébergement, audit SEO & sécurité | ✅ | Positionnement tenu dans tout le site (hero, services, méta SEO) |
| 2 | Stack : Laravel + Vite en back, React en front, MySQL en base | ✅ | Laravel 11 + Vite 6 + React 18 + Inertia.js ; SQLite par défaut, MySQL via `.env` |
| 3 | Page d'accueil | ✅ | `resources/js/Pages/Public/Home.jsx` + `PublicController@home` |
| 4 | Système d'audit (en ligne, libre-service) | ✅ | `AuditRunner`, `AuditController`, `Public/Audit.jsx`, `Public/AuditResult.jsx` |
| 5 | Présentation des prestations | ✅ | `Public/Services.jsx` + `PrestationCatalog` (7 prestations) |
| 6 | Page contact | ✅ | `Public/Contact.jsx` + `ContactController` (rate-limit + honeypot) |
| 7 | Mentions légales | ✅ | `Public/Mentions.jsx` |
| 8 | CGV | ✅ | `Public/Cgv.jsx` |
| 9 | Authentification 2FA pour l'admin | ✅ | TOTP via `pragmarx/google2fa` + QR code SVG (`bacon/bacon-qr-code`) |
| 10 | Espace admin pour gérer tout le site | ✅ | `/admin/*` : dashboard, audits, messages |
| 11 | Statistiques de visite | ✅ | Middleware `TrackVisit` + modèle `PageVisit` + dashboard admin (7j / 30j / uniques / top pages) |
| 12 | Design épuré et professionnel | ✅ | Tailwind 3, palette indigo/slate, composants épurés, grilles responsives |
| 13 | Sécurité cible 100/100 | ✅ | Voir [§ 4. Sécurité](#4-sécurité) — tous les en-têtes critiques sont renvoyés par défaut |
| 14 | SEO avec SSR | ✅ | SSR Inertia (`npm run build:ssr` → `bootstrap/ssr/ssr.js`), balises `<title>`, meta description, Open Graph, Twitter Card, canonical, `lang`, `viewport` injectées par page |
| 15 | Mots-clés SEO : audit SEO, audit de sécurité, développement web, hébergement web, création de SaaS | ✅ | Présents dans les balises meta des pages, les contenus visibles et le catalogue de prestations |
| 16 | Automatiser les audits pour que les clients soient autonomes | ✅ | Formulaire public `/audit`, exécution synchrone (pas de queue à opérer), rapport accessible par UUID (`/audit/{uuid}`) sans authentification |
| 17 | Prestations automatiques payantes à proposer sur le système | ✅ | `PrestationCatalog` expose 5 prestations tarifées (monitoring mensuel 49€/mois, hébergement managé 19€/mois, remédiation 390€, création de SaaS sur devis, développement web sur devis) — affichage en haut de page et en cross-sell sur le rapport d'audit |

---

## 2. Architecture du code

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── PublicController.php         # home, services, contact GET, mentions, cgv
│   │   ├── ContactController.php        # POST /contact (valid. + honeypot + throttle)
│   │   ├── AuditController.php          # GET/POST /audit + GET /audit/{uuid}
│   │   └── Admin/
│   │       ├── AdminDashboardController.php   # stats, top pages, recent
│   │       ├── AdminAuditController.php       # liste + détail audit
│   │       ├── AdminContactController.php     # liste + détail + update statut
│   │       └── TwoFactorController.php        # setup, enable, challenge, verify, disable
│   └── Middleware/
│       ├── SecureHeaders.php            # CSP, HSTS, X-Frame, Referrer-Policy, ...
│       ├── TrackVisit.php               # analytics RGPD (IP hashée)
│       ├── EnsureAdmin.php              # 403 si utilisateur non admin
│       └── Require2FA.php               # force setup/challenge 2FA pour /admin
├── Models/
│   ├── User.php                         # + is_admin, google2fa_secret (chiffré), google2fa_enabled
│   ├── PageVisit.php                    # url, ip_hash, referer, user_agent
│   ├── ContactMessage.php               # name, email, subject, message, status
│   └── Audit.php                        # uuid, url, status, score_seo/security/total, results JSON
└── Services/
    ├── AuditRunner.php                  # moteur d'audit SEO + sécurité
    └── PrestationCatalog.php            # catalogue des prestations

resources/js/
├── Layouts/
│   ├── PublicLayout.jsx                 # header + footer + SEO <Head>
│   └── AdminLayout.jsx                  # navigation admin
├── Pages/
│   ├── Public/{Home,Services,Contact,Mentions,Cgv,Audit,AuditResult}.jsx
│   └── Admin/{Dashboard,Audits,Messages,TwoFactor}.jsx
└── ...

database/migrations/
├── 2026_04_19_100000_create_page_visits_table.php
├── 2026_04_19_100001_create_contact_messages_table.php
├── 2026_04_19_100002_create_audits_table.php
└── 2026_04_19_100003_add_admin_and_2fa_to_users_table.php
```

---

## 3. Fonctionnalités

### 3.1 Pages publiques

- **Accueil** (`/`) : hero, statistiques, présentation des prestations, exemple d'audit, CTA.
- **Prestations** (`/prestations`) : 7 fiches prestations.
- **Contact** (`/contact`) : formulaire validé, anti-spam honeypot, throttle 5 req/min/IP.
- **Mentions légales** (`/mentions-legales`), **CGV** (`/cgv`).

### 3.2 Audit en libre-service

Parcours :
1. L'utilisateur saisit une URL sur `/audit`.
2. `AuditController@store` crée un audit (statut `running`), délègue à `AuditRunner::run()`.
3. `AuditRunner` fait un `HTTP GET` (timeout 15 s), extrait les balises SEO, sonde les en-têtes de sécurité, et vérifie `robots.txt`/`sitemap.xml`.
4. Chaque contrôle est pondéré (`pass` = 100 %, `warn` = 50 %, `fail` = 0 %). Scores SEO / sécurité / total sont calculés sur 100.
5. L'utilisateur est redirigé vers `/audit/{uuid}` (page publique, partageable).

Contrôles SEO automatisés (10) : HTTP 200, `<title>`, meta description, `<h1>`, viewport, `lang`, canonical, Open Graph, Twitter Card, compression.

Contrôles sécurité automatisés (10) : HTTPS, HSTS, Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, en-tête Server masqué, absence de X-Powered-By, cookies Secure+HttpOnly.

Protection : 3 audits/h/IP, blocage des adresses `localhost` / `127.0.0.1` / plages privées (`10.*`, `172.16/12`, `192.168.*`) pour éviter le SSRF.

### 3.3 Catalogue de prestations automatiques

`App\Services\PrestationCatalog` fournit 7 prestations :

| Slug | Prix | Type |
|---|---|---|
| `audit-seo` | gratuit | service en ligne |
| `audit-securite` | gratuit | service en ligne |
| `monitoring` | 49 €/mois | abonnement |
| `hebergement-web` | 19 €/mois | abonnement |
| `developpement-web` | sur devis | prestation |
| `creation-saas` | sur devis | prestation |
| `remediation` | 390 € | forfait |

Les prestations payantes sont mises en avant sur le rapport d'audit (cross-sell) et sur la page `/prestations`.

### 3.4 Espace administrateur (`/admin`)

Protégé par trois couches successives :
1. `auth` — utilisateur connecté
2. `admin` — `is_admin = true`
3. `2fa` — setup forcé si pas encore activé, puis challenge TOTP à chaque session

Après activation, la clé TOTP est chiffrée en base (`cast: 'encrypted'`), un QR code SVG inline est généré via `bacon/bacon-qr-code`. Au login, `2fa_verified` est retiré de la session — chaque session doit repasser le challenge.

Le dashboard affiche : visites 7j / 30j, visiteurs uniques 30j (clés : hash d'IP distincts), audits totaux / 7j, messages totaux / non lus, top 10 des pages (30j), 5 audits et 5 messages récents.

### 3.5 Analytics (RGPD-friendly)

`TrackVisit` middleware — une ligne `page_visits` par GET public (hors admin, hors bots). **L'IP n'est jamais stockée en clair** : elle est hachée en SHA-256 avec le `APP_KEY` comme sel. Le hash permet de compter les visiteurs uniques sans identifier une personne.

---

## 4. Sécurité

Cible : score 100/100 sur les scanners (observatory.mozilla.org, securityheaders.com).

| Mesure | Où ? |
|---|---|
| CSRF | Middleware `VerifyCsrfToken` (Laravel) sur toutes les routes `web` |
| Cookies `HttpOnly` + `SameSite=lax` | `config/session.php` par défaut |
| Cookies chiffrés | Middleware `EncryptCookies` |
| Hash mot de passe | Bcrypt (coût 12 en prod) |
| 2FA TOTP pour l'admin | `pragmarx/google2fa` + secret chiffré en base |
| Rate limiting | `contact` 5/min/IP · `audit` 3/h/IP · `two-factor` 5/min |
| `X-Content-Type-Options: nosniff` | Middleware `SecureHeaders` |
| `X-Frame-Options: DENY` | Middleware `SecureHeaders` |
| `Referrer-Policy: strict-origin-when-cross-origin` | Middleware `SecureHeaders` |
| `Permissions-Policy` | Désactive caméra, micro, géoloc, FLoC |
| `Cross-Origin-Opener-Policy: same-origin` | Middleware `SecureHeaders` |
| `Cross-Origin-Resource-Policy: same-origin` | Middleware `SecureHeaders` |
| `Strict-Transport-Security` | Activé en HTTPS / production (2 ans, `preload`) |
| `Content-Security-Policy` stricte | `default-src 'self'`, `frame-ancestors 'none'`, `object-src 'none'`, `form-action 'self'`, `base-uri 'self'`, `upgrade-insecure-requests` |
| Anti-SSRF sur l'audit | Blocage `localhost`, `127.0.0.0/8`, `10/8`, `172.16/12`, `192.168/16` |
| Honeypot | Champ caché sur le formulaire de contact |
| IP hachée | Stockée sous forme SHA-256 (conformité RGPD) |
| HTTPS forcé en production | `URL::forceScheme('https')` dans `AppServiceProvider` |

---

## 5. SEO

- **SSR** activé : `npm run build:ssr` produit `bootstrap/ssr/ssr.js`, démarré avec `php artisan inertia:start-ssr`.
- Chaque page contrôle son `<title>`, `meta description`, `meta keywords`, `og:*` et `twitter:*` via le helper `<Head>` d'Inertia, alimenté par le prop `meta` côté controller.
- Les mots-clés ciblés apparaissent dans les contenus et dans les balises meta : **audit SEO**, **audit de sécurité**, **développement web**, **hébergement web**, **création de SaaS**.
- `lang="fr"` sur `<html>`, `robots: index, follow` par défaut.

---

## 6. Installation et exécution

### Prérequis

- PHP 8.3, Composer 2, Node 20+, npm 10+.
- MySQL 8 (optionnel — SQLite par défaut).

### Mise en route

```bash
# Dépendances
./composer.phar install
npm install

# Base de données (SQLite par défaut, déjà créée sous database/database.sqlite)
php artisan migrate --seed
# Admin par défaut : admin@kodem.fr / KodemAdmin!2026

# Build des assets
npm run build

# En développement, utilisez plutôt :
php artisan serve                 # http://127.0.0.1:8000
npm run dev                        # Vite HMR sur :5173

# SSR en production
npm run build
php artisan inertia:start-ssr
```

### Passer sur MySQL

Dans `.env`, décommentez :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kodem
DB_USERNAME=kodem
DB_PASSWORD=secret
```

Puis `php artisan migrate --seed`.

---

## 7. Cycle qualité appliqué

Pour chaque fonctionnalité, le cycle suivant a été exécuté :

1. **Développement** — modèle + migration + controller + vue.
2. **Vérification** — `php artisan migrate`, `php artisan route:list`, `composer dump-autoload` si besoin.
3. **Tests unitaires** — ajout de cas dans `tests/Feature/` ou `tests/Unit/`.
4. **Exécution** — `php artisan test`.
5. **Correction** — régressions corrigées (ex. : bug `$m[1]` dans `AuditRunner::regexFirst` lorsque le motif n'avait pas de groupe — remplacé par `preg_match` direct + helper défensif).
6. **Régression** — `php artisan test` à nouveau, puis `npm run build` (client + SSR), puis smoke test HTTP avec `php -S` sur `/`, `/audit`, `/cgv`.

### Rapport final

```
Tests:     56 passed (276 assertions)
Duration:  2.55s
Fichiers de test :
  tests/Feature/PublicPagesTest.php            (6 tests)
  tests/Feature/ContactTest.php                (4 tests)
  tests/Feature/AuditTest.php                  (4 tests)
  tests/Feature/SecurityHeadersTest.php        (1 test)
  tests/Feature/VisitTrackingTest.php          (3 tests)
  tests/Feature/AdminAccessTest.php            (6 tests)
  tests/Unit/AuditRunnerTest.php               (4 tests)
  tests/Unit/PrestationCatalogTest.php         (3 tests)
  tests/Feature/ProfileTest.php                (5 tests — héritage Breeze)
  tests/Feature/Auth/*                         (17 tests — héritage Breeze)
  tests/Feature/ExampleTest.php                (1 test)
  tests/Unit/ExampleTest.php                   (2 tests)

Build Vite :
  public/build/                                — 8.80 s
  bootstrap/ssr/                               — 1.50 s
```

### Points de vigilance connus

- L'audit est exécuté **de manière synchrone** dans la requête HTTP. Pour un gros volume ou des URL lentes, le prochain itéré logique est de déplacer vers `QUEUE_CONNECTION=database` + `php artisan queue:work`.
- Les **sauvegardes des pages Welcome, Dashboard et Auth** fournies par Breeze sont conservées (Breeze teste ces parcours). Elles ne sont pas exposées dans la navigation publique du site Kodem.
- L'envoi d'e-mails (notifications, reset password, alertes audit) utilise `MAIL_MAILER=log` en dev. En production, configurez un MTA (SendGrid, Mailgun, SMTP).

---

## 8. Commandes utiles

```bash
php artisan test                            # suite complète
php artisan test --filter=AuditTest         # une suite
php artisan route:list --except-vendor      # routes applicatives
php artisan migrate:fresh --seed            # reset complet + admin seedé
npm run build                               # build production (client + SSR)
npm run dev                                 # Vite HMR
```

---

## 9. Compte administrateur par défaut

```
Email    : admin@kodem.fr
Password : KodemAdmin!2026
```

Au premier login, le site redirige vers `/admin/2fa/setup` pour activer la 2FA (scan du QR code avec Google Authenticator / Authy / 1Password), puis vers le dashboard.

**En production**, changez ce mot de passe et supprimez le seed ou remplacez-le par une commande `artisan make:admin` interactive.
