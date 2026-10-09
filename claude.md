# Recentrage du site `kodem.fr`

Ce document guide la réécriture du contenu de `kodem.fr`. Il applique les deux votes POUR SOUS CONDITIONS du `directeur` du 2026-09-28 (recentrage du site, puis positionnement « outils métier sur mesure ») et `~/kodem/prospection/doctrine.md` §4. Le paiement et la facturation sont abandonnés depuis le même jour, par décision de l'actionnaire contre l'avis du directeur.

Objectif unique : qu'un prospect qui clique sur le lien de la signature trouve sur le site exactement ce que le message d'approche lui a annoncé. Il ne s'agit pas d'une refonte graphique.

---

## 0. Cadre du chantier (non négociable)

- **Enveloppe : 2 jours de travail maximum, 0 € de décaissement.**
- **Mise en ligne au plus tard le 2026-10-06.** Si `~/kodem/prospection-journal.md` compte moins de 15 envois ce jour là, le chantier s'arrête là où il en est.
- Ces 2 jours ne sont pas pris sur le temps réservé à la prospection. L'objectif de 30 envois au 2026-10-15 reste prioritaire.
- **Aucune refonte graphique.** On change le contenu, pas le design.
- La suite de tests reste verte. Les tests qui figent le contenu (`VitrineTest`, `PublicPagesTest`) sont mis à jour pour décrire le nouveau contenu, pas désactivés.
- Indicateur de revue : au moins une demande entrante ou une réponse de prospect qui passe le filtre dur d'ici le 2026-12-31. Sinon, plus aucun jour sur le site.

---

## 1. Positionnement

KODEM ne se présente pas comme « développeur Laravel ». C'est le profil le plus banal du marché, il se négocie entre 350 et 450 € par jour et ne justifie pas 600 €.

**Métier affiché**, voté le 2026-09-28 et gelé jusqu'au 2026-10-15 :

> « KODEM conçoit des outils métier sur mesure qui suppriment les tâches manuelles : mises en service, inspections, documents, synchronisations entre logiciels. »

Le site présente ce métier large ; chaque message de prospection reste étroit (un secteur, un processus, une preuve). La page d'un prospect qui clique doit retrouver son cas parmi les trois secteurs de la section 2.

Preuves : MUXEN, outil **conçu et écrit de zéro** pour un opérateur (attribution DHCP automatique, génération de configurations VPN), jamais présenté comme une reprise ; Freendzy, rigueur en production (30 abonnés payants, tests, intégration continue).

Ne figurent plus sur le site : le paiement, la facturation, les abonnements, les marketplaces et Stripe, ni comme offre, ni comme preuve ; la reprise du code d'un tiers comme spécialité ; l'hébergement touristique et l'état des lieux (vote CONTRE jusqu'au 2026-10-15).

---

## 2. À qui parle le site

Les trois secteurs d'octobre de `doctrine.md` §4, dans cet ordre :

1. **Opérateurs réseau, FAI, MSP, intégrateurs télécom** : « vos mises en service clients et vos configurations sont encore faites à la main ». Preuve MUXEN.
2. **ESN et agences de 10 à 200 personnes** qui sous traitent la conception d'outils métier : capacité de conception de la base de données à la mise en production.
3. **PME de plus de 20 salariés** dont les équipes ressaisissent les mêmes données dans plusieurs logiciels : commandes, facturation fournisseur, stock, planning, dossiers clients.

Le site ne s'adresse ni aux artisans, ni aux professions libérales isolées, ni aux associations, ni aux particuliers.

---

## 3. Accueil (hero)

- **Titre :** « Des outils métier sur mesure qui suppriment vos tâches manuelles. »
- **Sous titre :** « KODEM conçoit les outils qui automatisent vos mises en service, vos inspections, vos documents et les échanges entre vos logiciels, de la base de données à la mise en production. Pour les opérateurs réseau, les MSP, les ESN, les agences et les PME dont l'exploitation ne peut plus dépendre de ressaisies. »
- **Appel à l'action :** « Décrivez la tâche que vous voulez supprimer » vers le formulaire de contact.

Le « je » du dirigeant qui présente KODEM est acceptable. Le mot « freelance » et la formule « nos équipes » sont interdits.

---

## 4. Structure de la page d'accueil

1. **Hero** (section 3).
2. **Le problème :** des configurations réseau ou système écrites à la main, un provisioning client qui prend une demi journée, une tâche d'exploitation répétée chaque semaine, des erreurs de saisie qui coupent un service. Des situations concrètes, pas d'adjectifs.
3. **Les trois secteurs** (section 2), chacun avec son processus type et sa preuve.
4. **Réalisations** (section 5).
5. **Parcours du dirigeant**, en quelques lignes et avec les faits de la section 5.
6. **Contact** (section 7).

---

## 5. Chiffres et preuves autorisés

Le site n'affiche **que des chiffres sourcés**. Tout chiffre est recompté le jour de la mise en ligne.

| Élément | Formulation autorisée | Source |
| --- | --- | --- |
| Freendzy, clients | « 30 abonnés payants en production » | `finances.md`, audité |
| Freendzy, tests | « 619 tests automatisés (unitaires, fonctionnels et de bout en bout), avec intégration continue et analyse statique » | dépôt Freendzy, à recompter le jour de la mise en ligne |
| Freendzy, utilisateurs | à afficher seulement s'il est recompté en base le jour de la mise en ligne | déclaratif à ce jour, non vérifié |
| MUXEN | « projet livré (2023 à 2025) : attribution DHCP automatique, génération de configurations VPN » | `finances.md` ; collaboration terminée le 2026-09-20, ne pas présenter comme client en cours |
| Vivaticket | expérience du dirigeant, avec son **intitulé de poste réel** (technicien support fonctionnel, salarié, 2017 à 2023) | CV ; le nombre de sites n'est pas affiché tant qu'il n'est pas sourcé |
| Expérience | « développeur professionnel depuis 2021, dans le métier depuis 2017 » | profil |

### Formulations interdites

- « 20 ans d'expérience »
- « administrateur système »
- « 619 tests unitaires »
- « parc critique de 300 sites » (non sourcé)
- « freelance »
- « nos équipes »
- « développeur backend PHP et Laravel » comme accroche
- les formules creuses (« passionné », « dynamique », « sur mesure » sans fait derrière)

---

## 6. Ce qui sort de l'accueil et du menu principal

- Les audits à 29 €, le monitoring à 49 €, l'hébergement à 19 €, la remédiation à 390 €.
- Les « dispositifs connectés sur site » (bornes, écrans).
- Les `// TODO` de `content/positioning.json`, remplacés par des chiffres de la section 5.
- Les listes de logos de technologies.

Ces pages restent accessibles par leur URL. **Arrêter ces produits commercialement est une décision distincte qui passe par `/kodem`** : elle n'est pas prise ici.

Le Stripe Checkout qui encaisse ces audits dans le code du site n'est pas concerné et ne se touche pas.

---

## 7. Contact

- **Une seule adresse : `mathieu.siaudeau@kodem.fr`**, testée en réception le jour de la mise en ligne.
- `contact@kodem.fr`, affichée par défaut dans `ContactBlockMono.jsx`, est remplacée, sauf si elle est prouvée fonctionnelle avant la mise en ligne.

---

## 8. Hors du périmètre de ce chantier (passe par `/kodem`)

- L'arrêt commercial des produits à 29 €, 49 €, 19 € et 390 €.
- La transformation du diagnostic en offre à prix fixe.
- Toute refonte graphique.

L'abandon du paiement a été décidé par l'actionnaire le 2026-09-28 contre l'avis du directeur (registre). Le `directeur` passerait à CONTRE sur le chantier du site si la mise en ligne conservait « 20 ans » ou « administrateur système ».
