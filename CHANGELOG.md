# Changelog

Toutes les évolutions notables de ce projet sont consignées dans ce fichier.

Le format s'inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/)
et le projet suit le [versionnage sémantique](https://semver.org/lang/fr/).

## [1.16.1] — 2026-08-06

### Fixed
- **Un seul `<title>` par page dans le HTML brut, et c'est le bon.** `app.blade.php` déclarait
  un `<title inertia>{{ config('app.name') }}</title>` en dur *et* `@inertiaHead` en injectait
  un second issu du SSR. Le HTML servi en contenait donc deux, le générique en premier — or la
  spec HTML n'en admet qu'un et c'est le premier qui fait foi. Résultat mesuré : tout crawler
  sans JavaScript lisait « Kodem » sur **toutes** les pages, malgré le SSR actif. Le `<title>`
  du Blade est supprimé ; le titre est désormais rendu par le seul `@inertiaHead`.
- **Suffixe de marque dupliqué dans les titres.** Les meta titles publics se terminent déjà par
  « | Kodem » (`PublicController`), et les deux points d'entrée y rajoutaient `` `${title} - ${appName}` ``,
  donnant « … | Kodem - Kodem ». Nouveau helper partagé `resources/js/lib/pageTitle.js`, utilisé
  par `app.jsx` et `ssr.jsx` : le suffixe n'est ajouté que si le titre ne porte pas déjà la marque.
  Les pages à titre court (« Log in », « Statistiques ») continuent de le recevoir.
- **Fautes d'orthographe** relevées par un balayage complet du corpus français (dictionnaire
  hunspell fr étendu aux formes fléchies + relecture grammaticale) :
  « trés » → « très » et majuscule après le point dans le témoignage de `content/testimonials.json`
  (visible sur la page d'accueil) ; « en-dessous » → « en dessous » (le trait d'union est fautif)
  dans l'e-mail de relance d'audit et dans l'aide de `audits:send-followup`.

### Notes
- La suppression du `<title>` du Blade a une contrepartie assumée : **si le process SSR n'est pas
  démarré**, le HTML brut ne contient plus aucun `<title>`. Dans cet état, il ne contenait déjà ni
  `<h1>`, ni `description`, ni `canonical`, ni la moindre balise `og:`/`twitter:` — un `<title>`
  générique n'y changeait rien. Le SSR (`php artisan inertia:start-ssr`) n'est supervisé par aucun
  service dans le dépôt : s'il tombe, Inertia repasse silencieusement en rendu client et le site
  perd tout son référencement sans lever d'erreur. À traiter séparément.

## [1.16.0] — 2026-07-30

### Added
- **Système d'animation 100 % CSS, sans une ligne de JavaScript ajoutée.** Trois primitives
  tokenisées (`animate-kodem-fade`, `animate-kodem-rise`, `animate-kodem-slide`) déclarées dans
  `tailwind.config.js`, deux motifs composés (`.kodem-card` élévation au survol, `.kodem-link`
  filet révélé) dans `resources/css/app.css`, et un budget de mouvement unique en `:root`
  (durées 220/420 ms, deux courbes, amplitude 14 px). Aucune dépendance ajoutée : `package.json`
  est inchangé.
- **Garde-fou `prefers-reduced-motion`** (WCAG 2.1 AA, critère 2.3.3), jusqu'ici totalement absent
  du site. Double barrière : neutralisation ciblée des primitives KODEM, puis filet universel à
  `0.01ms` couvrant Headless UI, la barre de progression Inertia et toute dépendance future. La
  valeur `0.01ms` — et non `0s` — est délibérée : une durée strictement positive garantit que
  `transitionend` est toujours émis, ce dont dépend Headless UI pour démonter ses panneaux.
- États interactifs (survol / focus visible / actif) sur les liens de navigation, les boutons et
  les cartes cliquables des surfaces publiques.
- **Révélation au scroll, sans observateur JavaScript** : `.kodem-reveal` s'appuie sur
  `animation-timeline: view()` et `animation-range: entry 0% cover 40%` (courbe `linear`, amplitude
  32 px — le contenu sous la ligne de flottaison n'étant jamais candidat LCP, le mouvement peut y
  être plus franc qu'au premier écran), appliqué aux sections de
  second niveau de 11 pages longues. Encapsulé dans `@supports (animation-timeline: view())` **et**
  `@media (prefers-reduced-motion: no-preference)` : hors de ce bloc la classe n'a aucune règle,
  donc aucun état masqué — un navigateur sans support affiche simplement le contenu. Support
  vérifié empiriquement (Chrome 150 : `view()` et `animation-range` à `true`) et comportement
  mesuré au navigateur : hors écran `opacity: 0` / `translateY(32px)`, puis progression étalée sur
  toute la hauteur d'écran (0,06 en bas de viewport → 0,47-0,89 en traversant le milieu → 1 en
  haut). Les trois réglages viennent de la mesure : `animation-duration` est sans effet sur une
  timeline `view()`, une courbe ease-out y est doublement amortie et écrase la fin de course, et
  une plage trop courte (`cover 25%`) terminait la révélation dans les 250 px du bas de l'écran —
  une bande qu'on ne regarde jamais en défilant.
- **Les grilles de cartes se révèlent carte par carte**, pas section par section (`.kodem-reveal-grid`) :
  chaque carte a sa propre timeline `view()` liée à sa propre position, la cascade est donc gratuite.
  Les cartes d'une même rangée entrant à la même hauteur, elles sont décalées en allongeant la plage
  colonne par colonne (`cover 40%` → `52%` → `64%`) — `animation-delay` étant ignoré sur une timeline
  de scroll, le décalage ne pouvait pas venir d'un délai. Modificateurs explicites `--2` / `--3` /
  `--4` plutôt qu'un modulo unique, les grilles n'ayant pas toutes le même nombre de colonnes, et
  décalages encadrés en `@media (min-width: 768px)` puisque les grilles sont en une colonne en deçà.
  Mesuré au navigateur sur une grille à 4 colonnes : opacités `1 / 0,84 / 0,69 / 0,58` en cours
  d'entrée — une vague en diagonale, pas une apparition simultanée. Les blocs qui ne sont pas une
  répétition d'éléments (intros, FAQ, CTA, corps d'article) gardent `.kodem-reveal`.
- Cascade `.kodem-stagger` disponible pour les grilles du premier écran (timeline du document).
  Elle ne doit **jamais** être combinée à `.kodem-reveal` : `animation-delay` est sans effet sur une
  timeline `view()`, la combinaison serait un bug silencieux — un test l'interdit.
- Animations d'entrée sur le premier écran de chaque page, **rejouées à chaque navigation** :
  `resources/js/app.jsx` monte le client via `createRoot().render()` et Inertia remonte le
  composant de page avec une clé neuve à chaque visite, donc les `@keyframes` d'entrée
  redémarrent sans aucun pilotage JavaScript.
- `tests/Unit/MotionGuardTest.php` — 13 tests de garde en scan statique de fichiers, tous prouvés
  par mutation. Le SSR étant inactif en tests, c'est la seule façon de garantir un invariant
  portant sur du JSX ou du CSS.

### Changed
- Les primitives n'animent que `transform` et `opacity` : aucune propriété déclenchant un reflow
  n'est animable, ce qu'un test verrouille. CLS mesuré à 0 sur les pages animées.

### Notes
- **`animate-kodem-slide` (translation seule, sans opacité) est obligatoire sur les pages sans
  bannière.** Mesure Lighthouse du 2026-07-30 sur `/contact`, serveur local, assets buildés, à
  FCP constant (2,6 s) : `animate-kodem-rise` (qui démarre à `opacity: 0`) donne un **LCP de
  4,2 s**, contre **3,6 s** sans animation **et 3,6 s avec `animate-kodem-slide`**. Chrome ne
  retient pas comme candidat LCP un élément peint à opacité nulle ; un élément seulement translaté
  reste peint, donc éligible. Le mouvement est conservé sans coût sur le LCP.
- Aucune animation sur le `<h1>` ni sur l'`<img>` de `Banner.jsx`, ni sur aucun `<h1>` de la
  surface publique, ni sur le chrome persistant de `PublicLayout.jsx` (header collant et pied de
  page, qui remontent à chaque navigation). Trois tests distincts verrouillent ces exclusions.
- Hors périmètre CSS, et définitivement : les transitions inter-pages de type *View Transition*.
  `@view-transition` est **cross-document** ; Inertia navigue en same-document et exigerait
  `document.startViewTransition()`, donc du JavaScript. Les animations d'entrée qui rejouent à
  chaque navigation en tiennent lieu.

## [1.15.0] — 2026-07-28

### Added
- **Zone d'intervention étendue à toute la France.** Le site annonce désormais une intervention
  **partout en France** (home, contact, expertises, zone d'intervention, pied de page global),
  alors qu'il se limitait jusqu'ici à la Nouvelle-Aquitaine. **Poitiers reste la base physique
  affichée** : le NAP, le `PostalAddress` (ville, région, code postal), les coordonnées `geo` et
  le `<title>` de `/zone-intervention` la conservent — seule la zone **desservie** (`areaServed`)
  devient nationale, ce qui ouvre le périmètre commercial sans sacrifier le référencement local.
- Nouvelle section **« Modèle d'intervention »** sur `/zone-intervention` (préparation à distance →
  pose planifiée → supervision et retour) : elle explique le mécanisme qui rend la couverture
  nationale crédible, plutôt que de l'affirmer sans justification.
- Prop optionnel `area` sur `ContactBlockMono`, rendu conditionnellement (défaut vide : aucun
  changement dans le pied de page).

### Changed
- **Promesse de réactivité graduée** au lieu d'uniforme : déplacement sur site **le jour même en
  Nouvelle-Aquitaine, sous 48 h partout ailleurs en France**. Une promesse « jour même » nationale
  aurait été intenable et relèverait de la publicité trompeuse ; un test de garde-fou
  (`tests/Unit/CopyGeoGuardTest.php`) échoue désormais si une occurrence de « jour même » apparaît
  quelque part sans sa graduation.
- JSON-LD homogénéisé : les nœuds `Organization`, `LocalBusiness` et `Service` partagent une même
  variable `$areaServedFrance` (`{"@type":"Country","name":"France"}`), là où trois représentations
  différentes cohabitaient (`'FR'`, `'Nouvelle-Aquitaine'`, `'FR'`). Toujours un seul `json_encode`
  sur le graphe final avec les flags durcis.

### Fixed
- Incohérence NAP : `/zone-intervention` affichait « Poitiers, Nouvelle-Aquitaine » (virgule) alors
  que le reste du site utilise « Poitiers — Nouvelle-Aquitaine » (tiret cadratin). L'override est
  supprimé, une seule graphie subsiste, alignée mot pour mot sur le `PostalAddress` du JSON-LD.

## [1.14.0] — 2026-07-27

### Changed
- **Modèle commercial de l'audit revu.** L'audit en ligne devient un **aperçu gratuit** (score global) :
  le déblocage automatique du rapport à 29 € est retiré, ainsi que les options PDF (9 €) et
  Core Web Vitals (19 €). La **seule offre payante** est désormais le **rapport SEO & sécurité complet**,
  **rédigé manuellement**, à **150 €**, **transmis sous 24 à 48 h** par lien privé valable 90 jours.
- Prix porté de 149 € à 150 €, et régime de TVA centralisé dans `config/audits.php` :
  franchise en base, mention « TVA non applicable, art. 293 B du CGI » affichée sur la page de vente,
  dans Stripe et dans les CGV. Aucune mention « HT » : rien ne s'ajoute au prix affiché.
- La page de vente `/audits/premium` existait mais était **inatteignable** (aucun lien) et **sans contenu**
  (formulaire nu). Elle est désormais rédigée — contenu du rapport, prix, délai, nature manuelle,
  restriction aux professionnels — et liée depuis `/audit` et la page de résultat.
- CGV complétées : nouvel article dédié au rapport complet (prix, délai, livraison, réservé aux
  professionnels, pas de rétractation), et régime de TVA explicité.

### Fixed
- Les accès déjà achetés sont préservés : un audit payé conserve son rapport complet, et les options
  PDF / Core Web Vitals déjà réglées restent accessibles — seules leurs offres de vente disparaissent.

## [1.13.4] — 2026-07-27

### Fixed
- **Les formulaires ne réagissaient plus en développement local.** `SecureHeaders` ajoutait la directive CSP
  `upgrade-insecure-requests` sans condition : le navigateur réécrivait chaque requête `http://` en `https://`,
  et sur un serveur de dev sans TLS le `POST` partait vers une adresse inexistante (`AxiosError: Network Error`).
  La requête atteignait bien le serveur, mais la réponse n'arrivait jamais et l'interface restait figée.
  La directive suit désormais la même condition que HSTS (connexion sécurisée ou production), donc reste
  active sur tout déploiement HTTPS, y compris derrière un reverse proxy TLS.
- **Les polices de la charte n'étaient jamais chargées**, sur toutes les pages et dans tous les environnements :
  le CSP n'autorisait pas `fonts.bunny.net`, pourtant référencé par `app.blade.php`. Ajouté à `style-src`
  et `font-src` ; Space Grotesk et JetBrains Mono s'affichent enfin.
- **Limite de débit atteinte : plus de silence.** Une 429 brute n'étant pas interprétable par Inertia,
  l'écran ne bougeait pas et l'utilisateur croyait le bouton cassé. Les requêtes Inertia reçoivent maintenant
  un message explicite (« Trop de tentatives… Réessayez dans N minutes »), affiché sur la page d'audit ;
  les clients non-navigateur continuent de recevoir une vraie 429 avec `Retry-After`, et la limite elle-même
  est inchangée. Les blocages sont journalisés (`Log::warning`) puisqu'ils n'apparaissent plus en 429.
- Ajout d'une prop Inertia partagée `flash` (`success` / `error`), sans laquelle aucun message de session
  n'atteignait les composants React.

## [1.13.3] — 2026-07-27

### Fixed
- Récupération des données réelles de l'ancien site techcaresolutions.fr (claude.md « FIX / récupération de données ») :
  import des 5 témoignages clients réels dans `content/testimonials.json` (verbatims exacts, sans note étoilée
  conformément à la charte §4) en remplacement des 2 entrées d'exemple `"Prénom Nom"`, et import du visuel
  du cas `carte-qr-manhattan-cafe` en WebP (`public/images/realisations/`, EXIF supprimé, 21 ko).
  Les cas `photomaton`, `ecran-raspberry` et `controle-acces-billetterie` conservent leur sentinelle `// TODO` :
  aucun visuel disponible ne les illustre réellement.
- `CaseImage` : contrat de donnée durci (seul un chemin `/images/realisations/<slug>.webp` est accepté) et
  chargement `eager` + `fetchpriority=high` sur le variant `hero`, désormais candidat LCP.

<!--
Versions antérieures à 1.13.3 : non consignées.
La baseline 1.13.2 a été reconstituée à partir de l'historique git (13 fonctionnalités, 2 correctifs).
-->
