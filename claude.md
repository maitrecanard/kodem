# CLAUDE.md — Site web KODEM

> Fichier de contexte projet pour Claude Code.
> À placer à la racine du dépôt : Claude Code le lit automatiquement à chaque session.
> La section §9 « TÂCHES » est exécutable telle quelle (« exécute les tâches du CLAUDE.md »).

---

## 0. Décision de positionnement — HYPOTHÈSE DE TRAVAIL

> ⚠️ **`POSITIONNEMENT_VALIDÉ = false`**
> Le positionnement ci-dessous est une hypothèse à confirmer par un test terrain
> (≈15 prospects contactés en direct) AVANT mise en production.
> Tant que ce flag est `false` : tout le copy de positionnement vit dans
> `/content/positioning.*` et nulle part ailleurs, pour pouvoir le repointer
> sans toucher au reste du site.

**Positionnement retenu :** KODEM conçoit et déploie des **dispositifs logiciels
connectés sur site** — bornes, écrans pilotés, photomatons, installations
interactives — et en assure le pilotage et la sécurité dans la durée.

Concrètement :
- **Cœur de métier** = le logiciel qui pilote du matériel physique, sur le lieu du
  client. C'est précisément ce qu'une IA ou une agence web à distance ne fait pas.
- Les quatre expertises de la charte (dev / hébergement / SEO / sécurité) deviennent
  des **capacités de support**, pas le titre. On les garde, on ne les met pas en façade.
- **Cible primaire** : secteurs à présence physique — événementiel, retail / PLV,
  hôtellerie-restauration, musées & lieux culturels, collectivités, signalétique dynamique.
- **Ancrage local assumé** (Nouvelle-Aquitaine / Poitiers) : c'est un atout pour de
  l'installation sur site, pas une limite.

**Pourquoi ceci prime sur le texte de la charte (page 3) :** la charte décrit l'ancien
positionnement « cycle numérique complet » (dev + host + SEO + sécurité, PME + grands
comptes + secteur public). C'est un positionnement de commodité : large, indifférencié,
en concurrence frontale avec des fermes de contenu et invisible en SEO 2026. Le visuel
de la charte (crochets, grille, mono, esthétique d'ingénierie) colle en revanche
parfaitement à la niche « dispositifs connectés ». On conserve le visuel, on recadre le message.

> Pour basculer en mode générique « agence 4 expertises » : mettre `POSITIONNEMENT_VALIDÉ`
> à `true` et remplacer le contenu de `/content/positioning.*` par la version page 3 de la charte.

---

## 1. Contexte

- Marque : **KODEM** — `kodem.fr`
- Livrable : site vitrine technique + fondations SEO/GEO, statique et performant.
- Public : décideurs non techniques côté client (événementiel, retail, culture,
  collectivités) → le site doit **prouver la compétence technique sans jargonner**.
- Objectif business : générer des demandes qualifiées sur la niche, pas du trafic de masse.

---

## 2. Charte graphique — contraintes NON négociables

**Couleurs (tokens) :**
| Token        | HEX       | Usage                                                |
|--------------|-----------|------------------------------------------------------|
| `cobalt`     | `#2348E0` | Primaire — accent uniquement (liens, boutons, données clés). **Jamais en aplat dominant.** |
| `indigo`     | `#0B1B4D` | Fonds foncés & texte                                 |
| `brume`      | `#E8ECFF` | Fonds clairs secondaires, séparateurs                |
| `acier`      | `#64748B` | Texte secondaire, métadonnées                        |
| `papier`     | `#FBFCFE` | **Fond dominant (~60 % de la surface)**              |

Répartition imposée : **Papier 60 % / Indigo (structure) / Cobalt (accent ponctuel)**.

**Typographie :**
- `Space Grotesk` — titrage & texte courant. Graisses 400 / 500 / 600 / 700.
- `JetBrains Mono` — labels, métadonnées, extraits de code, micro-annotations.
- Échelle : Display 56 · H1 34 · H2 24 · Corps 15 (interligne **1.65**) · Légende 11.
- Polices **self-hosted** (perf + RGPD), `font-display: swap`.

**Logo :**
- `[kodem]` bas-de-casse, crochets solidaires du mot (jamais isolés).
- Crochets cobalt sur fond clair / blancs sur fond foncé.
- Symbole `[k]` réservé favicon / avatar / petites tailles.
- Taille mini écran : 24 px. Marge de protection = hauteur d'un crochet de chaque côté.
- **Interdits** : déformer, recolorer hors palette, incliner, ombre portée, fond chargé, contour.

**Système visuel :**
- Grille 8 pt pour toutes les mises en page.
- Motif diagonal cobalt/brume très léger pour fonds, séparateurs, réserves d'image.
- Boutons : `Primaire` (cobalt plein) · `Secondaire` (contour) · style mono `action()` pour l'accent.
- Iconographie : style ligne, trait 1.5 px, angles arrondis, cobalt sur fond clair.

**Accessibilité :** contraste **AA minimum** sur tout texte. Voile indigo sur images si texte par-dessus.

---

## 3. Ton éditorial (ce qui rend les textes fidèles à la charte ET citables par les IA)

Valeurs charte : **rigueur, fiabilité, transparence, maîtrise technique.** Esthétique d'ingénierie.

> Point clé : la voix « ingénierie précise » et la stratégie SEO 2026 demandent **la même
> chose** — des affirmations directes, vérifiables, structurées. La marque et le SEO sont alignés.

**Règles d'écriture :**
1. **Réponse d'abord, preuve ensuite, action enfin.** (structure qui se fait citer par les moteurs IA)
2. Phrases courtes. Une idée par phrase.
3. **Affirmations vérifiables et chiffrées.** Pas de chiffre = on n'écrit pas la phrase.
4. Jargon marketing creux **banni** : « solutions innovantes », « à la pointe »,
   « sur-mesure » répété en boucle, « accompagner dans la transformation »… → supprimer.
5. La transparence se **montre** : on explicite les choix techniques, on assume les limites.
6. Aucun superlatif gratuit. La crédibilité vient de la précision, pas de l'emphase.
7. Exploiter le mono pour des micro-annotations techniques crédibles
   (`// uptime 99.9%`, `// déploiement < 48 h`) — renforce l'identité ET la preuve.

---

## 4. Stratégie SEO / GEO 2026

**Contexte (factuel, état du search en 2026) :**
- ~60–69 % des recherches Google se terminent sans clic ; les AI Overviews avalent
  l'informationnel. Les requêtes « comment faire X » sont cannibalisées à ~99,9 %.
- Le SEO de mots-clés génériques (« agence développement web ») est un canal mort.
- Ce qui survit : requêtes **transactionnelles, comparatives, de décision**, et le **local**
  (peu d'AI Overview, intention d'achat). **Être cité** dans une réponse IA = la nouvelle position 1.
- Levier d'un acteur de niche : l'**autorité thématique étroite** est atteignable et c'est
  exactement ce que les LLM citent.

**À FAIRE :**
- Contenu **answer-first** + données structurées (JSON-LD) → éligibilité à la citation IA.
- **Pages cas client chiffrées** = l'actif central. Template : *problème → contrainte technique
  → choix retenu et pourquoi → résultat chiffré.* Aimant à citations + moteur de conversion.
  Commencer par : le **photomaton** et l'**écran piloté par Raspberry**.
- **Pages comparatives / décision** (« borne sur mesure vs solution clé en main »,
  « écran piloté localement vs cloud ») — difficiles à synthétiser pour une IA, proches de l'achat.
- **SEO local** : Google Business Profile complet, page(s) zone d'intervention, **NAP cohérent**
  partout. Installation physique + requête locale = transactionnel, résistant aux AI Overviews.
- **Témoignages** : afficher en texte clair (nommés, attribués, chiffrés). Renforcent la conversion
  ET fournissent du signal de confiance citable par les IA.
- **Schema JSON-LD** : `Organization`, `LocalBusiness`, `Service`, `FAQPage`, `BreadcrumbList`.
- **Mesure** : suivre citations IA / requêtes de marque / conversion par source — pas que le ranking.

**À NE PAS FAIRE :**
- Blog de guides génériques « comment faire X » → cannibalisés à ~99,9 %. Pure perte.
- Bourrage de mots-clés génériques → invendable et invisible.
- Toute promesse non chiffrable.
- **`Review` / `AggregateRating` schema sur ses propres témoignages** : Google interdit le markup
  d'avis auto-promotionnels (avis sur sa propre entité, sur ses propres pages) → risque de pénalité.
  Les témoignages restent du **texte + photo**, jamais un rich snippet étoilé sur soi-même.

---

## 5. Arborescence

```
/                       Accueil — positionnement niche, preuve, témoignage, CTA
/realisations           Index des cas clients
/realisations/[slug]    Cas client chiffré (template §4) + témoignage du client concerné
/expertises             Dev / hébergement / SEO / sécurité — présentés EN SUPPORT
/zone-intervention      Page locale (SEO local)
/contact                NAP + formulaire
/notes        (option)  Notes techniques de fond (autorité/citation) — PAS des guides génériques
```

**Composant `Testimonial` (réutilisable) — règles :**
- Champs obligatoires : `citation`, `nom`, `fonction`, `structure`. Optionnel : `photo`, `cas_lié` (slug).
- Le contenu doit être **spécifique et chiffré** (ex. « borne déployée sur 3 jours d'événement,
  1 200 tirages, zéro plantage »), pas une formule de politesse.
- **Jamais de témoignage anonyme** : sur une marque « ingénierie », ça décrédibilise.
- **Jamais de témoignage inventé.** Si aucune donnée réelle → ne pas afficher la section (cf. §6).

---

## 6. Données à fournir par l'humain (bloquant pour la prod)

- [ ] **Cas clients** : chiffres réels (charge supportée, uptime, délai de déploiement,
      coût d'infra évité, nb d'installations…). Sans chiffre → pas de page cas.
- [ ] **NAP** : adresse réelle (la charte a `[adresse à compléter]`), téléphone réel
      (placeholder `+33 (0)0 00 00 00 00`), email. **Cohérence NAP = critique pour le local.**
- [ ] **Témoignages réels** : citation + nom + fonction + structure (+ photo si possible).
      À récolter auprès des clients photomaton / écran Raspberry. Sans réel → section masquée.
- [ ] **Jeu d'icônes** (charte : « à fournir »).
- [ ] **Visuels bannière** (charte : « image à insérer » — équipe / infrastructure / code).

> Marquer en `// TODO:` chaque emplacement où une donnée réelle manque ; ne jamais inventer de chiffre.

---

## 7. Stack (proposition — ajuster si besoin)

- **Astro** (ou Next.js en SSG) : statique, ultra-perf, idéal SEO.
- **Tailwind** configuré avec les tokens du §2 (couleurs, échelle typo, espacement 8 pt).
- Polices self-hosted. Images **WebP, < 300 ko**, lazy-load (cf. specs bannière de la charte).
- JSON-LD injecté par layout + par page.

---

## 8. Anti-objectifs (rappels)

- Ne **pas** réintroduire le positionnement « 4 expertises en façade » tant que `POSITIONNEMENT_VALIDÉ = false`.
- Ne **pas** produire de contenu informationnel générique pour « faire du volume ».
- Ne **pas** dégrader la charte (cf. interdits §2).

---

## 9. TÂCHES — exécutables

1. Initialiser le projet [stack §7] ; configurer Tailwind avec les tokens charte (§2).
2. Layout global : header `[kodem]`, footer avec NAP (placeholders `// TODO`), fond papier,
   motif diagonal en séparateur. Respecter grille 8 pt et accessibilité AA.
3. Créer `/content/positioning.*` (source unique du copy de positionnement niche, §0).
4. Rédiger le copy de chaque page (§5) selon le ton éditorial (§3) et la stratégie (§4) —
   answer-first, chiffré, sans jargon.
5. Construire le composant « cas client » (template §4) ; le câbler sur photomaton + écran Raspberry,
   avec `// TODO` sur chaque chiffre manquant.
6. Construire le composant `Testimonial` (§5) : sur l'accueil et au pied de chaque cas client.
   Si aucune donnée réelle fournie → rendre la section masquée par défaut (pas de placeholder bidon).
   **Ne pas** générer de `Review`/`AggregateRating` schema dessus.
7. Injecter le JSON-LD : `Organization` + `LocalBusiness` (layout), `Service` / `FAQPage` /
   `BreadcrumbList` (pages concernées).
8. Rédiger `title` + `meta description` answer-first par page. Aucun keyword stuffing.
9. Passe perf/access : AA, images < 300 ko WebP, lazy-load, polices `swap`, audit Lighthouse.
10. Lister en fin de run tous les `// TODO` (données du §6) à compléter avant mise en ligne.