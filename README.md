# ASIN — Suivi des demandes d'actes

Étude de cas pratique — **Développeur(se) junior(e)** — Agence des Systèmes d'Information et du Numérique (ASIN), République du Bénin.

Application de gestion des demandes d'actes administratifs (acte de naissance, casier judiciaire, certificat de résidence) : API Laravel, espace usager et espace agent en Vue.js.

- **Candidat(e) :** Toyin Spéro Mériem AKPO

---

## Application

Le dépôt GitHub utilise la branche **`master`** :

```bash
git clone -b master https://github.com/mcspirou6/asin-demandes-actes.git
cd asin-demandes-actes
```

| Espace | URL locale | Accès de démonstration |
|---|---|---|
| Usager | http://127.0.0.1:8000 | Aucun compte requis. NPI de test : `0123456789` ou `0466170591`. Le code de suivi est remis après le dépôt. |
| Agent | http://127.0.0.1:8000/admin | Email : `agent@asin.bj` · Mot de passe : `demo1234` |

Codes de suivi préchargés par le seeder : `ASIN-DEMO01` (usager 1) et `ASIN-DEMO06` (usager 2). Ces identifiants et données sont fictifs et réservés aux tests locaux.

---

## Présentation

Les usagers déposent en ligne des demandes d'actes administratifs. Chaque demande suit un cycle de vie strict :

```
déposée (submitted)
    ↓
en cours de traitement (processing)
    ├──────────→ validée (approved)
    │
    └──────────→ rejetée (rejected, motif obligatoire)
```

Le projet fournit :

1. une **API REST** (Laravel) qui gère le dépôt, la consultation et l'avancement des demandes ;
2. un **espace usager** (Vue.js) : dépôt d'une demande avec remise d'un **code de suivi**, puis suivi par ce code uniquement ;
3. un **espace agent** (validateur, `/admin`) : connexion par identifiants fictifs, dashboard, notifications de nouvelles demandes, traitement (prise en charge, validation, rejet motivé) ;
4. une **suite de tests automatisés** couvrant toutes les règles de gestion.

## Stack

| Élément | Technologie |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Base de données | SQLite (aucun serveur à installer) |
| Frontend | Vue 3 + Vite |
| Tests | PHPUnit / tests Laravel |
| Langage | PHP, JavaScript |

## Prérequis

- PHP >= 8.2 avec l'extension `pdo_sqlite` (inclus dans XAMPP)
- Composer >= 2
- Node.js >= 20 et npm

## Installation

```bash
git clone -b master https://github.com/mcspirou6/asin-demandes-actes.git
cd asin-demandes-actes

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## Base de données

**SQLite** est utilisé : la base est un simple fichier (`database/database.sqlite`), créé automatiquement à la première migration. Aucun serveur de base de données à installer ou configurer.

Charger un environnement de démonstration prêt à l'emploi (données 100 % fictives) :

```bash
php artisan migrate:fresh --seed
```

Après cette commande, la base contient :

- 5 demandes pour l'usager de démonstration **NPI `0123456789`** (au moins une par statut : `submitted`, `processing`, `approved`, `rejected`, et au moins un exemple de chaque type d'acte) ;
- 1 demande reproductible pour le second usager de démonstration **NPI `0466170591`** ;
- 7 demandes supplémentaires fictives pour alimenter l'espace agent de démonstration ;
- un **motif** sur chaque demande rejetée.

## Démarrage

Ouvrir **deux terminaux** à la racine du projet :

```bash
# Terminal 1 — API + interface (port 8000)
php artisan serve

# Terminal 2 — build frontend (ou `npm run dev` en développement)
npm run build
```

Puis ouvrir :

- **Espace usager :** http://127.0.0.1:8000 (déposer une demande et suivre avec son code)
- **Espace agent :** http://127.0.0.1:8000/admin (connexion, dashboard, notifications, traitement)
- **API :** http://127.0.0.1:8000/api/...

> En développement, `npm run dev` fonctionne aussi : Vite relaie les appels `/api` vers le port 8000 (proxy configuré dans `vite.config.js`).
> Après un changement frontend, reconstruire les assets avec `npm run build` et recharger la page sans cache (`Ctrl+F5`).

### Identifiants de démonstration de l'agent

```text
URL          : http://127.0.0.1:8000/admin
Email        : agent@asin.bj
Mot de passe : demo1234
```

Ces identifiants sont réservés à la démonstration locale et ne doivent pas être utilisés en production.

### Usagers fictifs pour tester

Il n'y a pas de compte usager à créer ou de connexion usager. Le NPI est saisi uniquement lors du dépôt ; après l'envoi, l'usager reçoit un code de suivi. Il doit conserver ce code et s'en servir pour suivre **sa demande**. L'espace usager ne montre ni le NPI, ni la liste des demandes d'autres personnes, ni les statistiques.

Après `php artisan migrate:fresh --seed`, tu peux tester avec les dossiers fictifs déjà présents :

| Usager de démonstration | NPI à utiliser pour déposer | Code déjà existant pour le suivi | État initial |
|---|---|---|---|
| Usager 1 | `0123456789` | `ASIN-DEMO01` | Déposée |
| Usager 2 | `0466170591` | `ASIN-DEMO06` | Déposée |

Tu peux aussi déposer de nouvelles demandes avec ces NPI et utiliser les codes affichés à la confirmation. Les NPI et codes du seeder sont des données fictives uniquement destinées aux essais.

### Test rapide des deux espaces

1. Ouvrir `http://127.0.0.1:8000` dans un onglet usager, choisir un acte, entrer le NPI de l'usager 1 ou 2, puis déposer sa demande.
2. Noter le code de suivi donné après confirmation : l'espace usager ne permet pas de retrouver une demande avec son NPI.
3. Ouvrir `http://127.0.0.1:8000/admin` dans un autre onglet, puis se connecter avec l'identifiant agent ci-dessus.
4. Retrouver la demande dans le tableau agent. Cliquer sur « Actualiser » si besoin. La liste des demandes et les NPI ne sont visibles que par l'agent connecté.
5. Cliquer sur « Prendre en charge », puis choisir de valider ou de rejeter. Un motif est obligatoire pour un rejet.
6. Revenir dans l'onglet usager, sélectionner « Suivre ma demande », saisir le code de suivi et actualiser le statut : le changement fait par l'agent est alors visible.

Les codes `ASIN-DEMO01` à `ASIN-DEMO05` appartiennent aux demandes de démonstration de l'usager 1 ; `ASIN-DEMO06` est celui de l'usager 2. Ces six codes sont fixes et reproductibles après chaque seed. Attention : `php artisan migrate:fresh --seed` efface les données actuelles et recrée la base de démonstration.

## Tests

```bash
php artisan test
```

La suite couvre : création et validation (NPI, type d'acte, copies), parcours complet du dépôt au suivi après traitement par l'agent, code de suivi public sans divulgation du NPI, retrait de la liste publique par NPI et des statistiques publiques, protection de l'espace agent (401), transitions interdites, rejet motivé, idempotence, authentification et chatbot.

## API

Base : `http://127.0.0.1:8000/api`

### POST /requests — déposer une demande

```json
{
    "npi": "0123456789",
    "act_type": "birth_certificate",
    "copies_count": 2
}
```

- `npi` : exactement 10 chiffres (stocké en `string` pour préserver les zéros initiaux).
- `act_type` : `birth_certificate` | `criminal_record` | `residence_certificate`.
- `copies_count` : entier entre 1 et 5 inclus.
- Le statut initial `submitted` et le **code de suivi** (`ASIN-XXXXXX`) sont **posés par le serveur** : le client ne peut pas les imposer.

Réponse : `201 Created` avec la demande créée et son code de suivi. Erreurs : `422 Unprocessable Entity`.

### GET /requests/track/{code} — suivre une demande par code de suivi

Public, utilisé par l'écran « Suivre ma demande ». Recherche insensible à la casse.

- Demande trouvée → `200` avec son état courant.
- Code inconnu → `404` : `{"message": "Aucune demande ne correspond à ce code de suivi."}`

### Espace agent — authentification

```text
POST /api/admin/login   { "email": "agent@asin.bj", "password": "demo1234" }
GET  /api/admin/me      → { "authenticated": true|false }
POST /api/admin/logout
```

- Identifiants fictifs (configurables via `.env` : `ADMIN_EMAIL`, `ADMIN_PASSWORD`), voir section « Démarrage ».
- Identifiants incorrects → `422` : `{"message": "Identifiants incorrects."}`
- Une fois connecté, une session marque l'agent ; le middleware `admin` protège les endpoints de traitement (`401` sinon).

### GET /api/admin/requests — vue agent (toutes les demandes)

Réservé à l'agent connecté. Filtres facultatifs : `?status=processing`, `?npi=0123456789`. Tri du plus récent au plus ancien. La taille de page est configurable avec `per_page=10`, `per_page=20` (valeur par défaut) ou `per_page=50`.

### GET /api/admin/stats — demandes en attente

Réservé à l'agent connecté ; renvoie le nombre de demandes déposées, jamais affiché dans l'espace usager.

### PATCH /requests/{id}/status — faire avancer le traitement

**Réservé à l'agent connecté** (`401` sans session). Le corps attendu reste :

```json
{ "status": "processing" }
```

```json
{ "status": "approved" }
```

```json
{ "status": "rejected", "rejection_reason": "Informations incorrectes." }
```

- La transition est **contrôlée côté serveur** (voir cycle de vie ci-dessous).
- Transition interdite → `409 Conflict` avec un message explicite, ex. :
  `{"message": "La transition de submitted vers approved est interdite."}`
- Rejet sans motif → `422 Unprocessable Entity` (`"Un rejet doit obligatoirement être motivé."`).
- Demande inexistante → `404 Not Found`.

### Cycle de vie des statuts

| Depuis ↓ / Vers → | processing | approved | rejected |
|---|---|---|---|
| **submitted** | ✅ | ❌ 409 | ❌ 409* |
| **processing** | ❌ 409 | ✅ | ✅ (motif requis, sinon 422) |
| **approved** (final) | ❌ 409 | ❌ 409 | ❌ 409 |
| **rejected** (final) | ❌ 409 | ❌ 409 | ❌ 409 |

\* la transition interdite est signalée en **409 avant** la validation du motif : une demande dans un état final ne peut pas être modifiée, même avec une requête incomplète.

## Choix techniques

- **Laravel 12** : framework mature, validation intégrée, ressources JSON, tests intégrés — idéal pour livrer une API propre très vite.
- **SQLite** : zéro configuration pour le jury ; parfaitement suffisant pour le volume de l'étude de cas. Le fichier `.env.example` pointe vers SQLite.
- **Architecture en couches** :

```
HTTP Request
     ↓
Route (routes/api.php)
     ↓
Controller (app/Http/Controllers/RequestController.php)   ← mince
     ↓
Form Request (app/Http/Requests/...)                      ← validation
     ↓
Service (app/Services/RequestStatusService.php)           ← logique métier
     ↓
Model / Eloquent (app/Models/Request.php)
     ↓
SQLite
```

- **Enums** : `App\Enums\ActType` et `App\Enums\RequestStatus` (castés sur le modèle). Aucune chaîne arbitraire dispersée dans le code ; le graphe de transitions est défini dans `RequestStatus::allowedTransitions()`.
- **Validation** : toute donnée client est considérée comme non fiable ; les FormRequests centralisent les règles (NPI `digits:10`, Enum pour l'acte, copies `1..5`).
- **Gestion des erreurs** : `422` validation, `404` introuvable, `409` transition interdite (`InvalidTransitionException` convertie dans `bootstrap/app.php`).
- **Tests** : suite Feature complète (`tests/Feature/RequestApiTest.php`), base SQLite `:memory:` via `phpunit.xml`.
- **Seeders/Factory** : données de démonstration fictives, reproductibles à l'identique.

### Idempotence

- **Changement de statut** : la répétition d'un appel est sans effet indésirable. Une fois `approved` ou `rejected`, toute nouvelle transition renvoie `409` : une répétition accidentelle ne peut jamais contourner le cycle de vie (testé).
- **Création de demande** : sans clé d'idempotence, une double soumission crée deux demandes distinctes (comportement testé et documenté). Une stratégie complète type `Idempotency-Key` (clé client + table de correspondance + fenêtre d'expiration) n'a pas été implémentée **par manque de temps** ; c'est la principale limite connue.

## Bonus réalisés

| Bonus | État |
|---|---|
| Pagination configurable de la liste agent (10, 20 ou 50 par page) | ✅ |
| Compteur des demandes en attente | Réservé à l'agent connecté |
| Tests automatisés des règles et du parcours complet | ✅ |
| Consultation publique des demandes par NPI | Non disponible ; suivi individuel par code |

## Interface

Deux interfaces distinctes, même bundle : l'application Vue est montée selon l'URL (`/` usager, `/admin` agent).

### Espace usager (`/`)

- Accueil « **Bienvenue, cher usager** » + « Que voulez-vous faire ? » ;
- cartes de dépôt des trois types d'actes (formulaire NPI + nombre de copies) ;
- confirmation avec **code de suivi** mis en évidence et consigne de conservation ;
- écran « **Suivre ma demande** » : saisie du code de suivi → état courant de cette seule demande ;
- actualisation du statut après traitement par l'agent, sans afficher le NPI ni les statistiques globales ;
- lien « **Vous êtes agent ?** » vers l'espace agent.

### Espace agent (`/admin`)

- Formulaire de connexion (email + mot de passe fictifs) ;
- dashboard avec **notifications** : bannière de nouvelles demandes déposées, rafraîchie automatiquement toutes les 30 s ;
- interface de tri : filtre par statut, recherche par NPI et choix de 10, 20 ou 50 demandes par page ;
- tableau de traitement avec date et heure de dépôt : **prise en charge** (submitted → processing), **validation**, **rejet avec saisie obligatoire du motif** ;
- dossiers clos (validés/rejetés) non modifiables, la transition est de toute façon contrôlée côté serveur.

Le style suit la référence visuelle **ANIP** : en-tête bleu nuit avec barre tricolore, fond bleu-gris clair, cartes blanches à bord fin avec pastilles d'icônes, accents orange pour les actions principales. Icônes SVG uniquement, aucun emoji.

## Assistant conversationnel (fonctionnalité additionnelle)

L'endpoint optionnel `/api/chatbot` répond à des questions prédéfinies sur le traitement des demandes ; il n'est pas affiché dans l'espace usager.

**Principe : sans intelligence artificielle.** Les réponses sont prédéfinies à l'avance dans `config/chatbot.php` (mots-clés → réponse). L'endpoint normalise la question (minuscules, sans accents), cherche la meilleure correspondance par mots-clés et renvoie la réponse préparée ; sans correspondance, une réponse de repli est renvoyée. Rien n'est généré dynamiquement.

```text
GET /api/chatbot?q=comment+suivre+ma+demande

{"matched": true, "answer": "Votre demande suit ce parcours : déposée, ..."}
```

Thèmes couverts : suivi du parcours, statuts, NPI, nombre de copies, types d'actes, rejet et motif, modification d'une demande finalisée, délais, frais. Testé dans `tests/Feature/ChatbotTest.php`.

## Structure du projet

```
app/
├── Enums/
│   ├── ActType.php
│   └── RequestStatus.php
├── Exceptions/
│   └── InvalidTransitionException.php
├── Http/
│   ├── Controllers/
│   │   ├── AdminController.php
│   │   ├── ChatbotController.php
│   │   └── RequestController.php
│   ├── Middleware/EnsureAdminAuthenticated.php
│   ├── Requests/
│   ├── Requests/
│   │   ├── StoreRequestRequest.php
│   │   └── UpdateRequestStatusRequest.php
│   └── Resources/RequestResource.php
├── Models/Request.php
└── Services/RequestStatusService.php

database/
├── factories/RequestFactory.php
├── migrations/2026_10_06_000001_create_requests_table.php
└── seeders/DatabaseSeeder.php

resources/js/App.vue          # interface Vue.js
routes/api.php                # routes de l'API
tests/Feature/RequestApiTest.php
```

## Limites connues (et pourquoi)

- **Authentification agent simplifiée** : un unique agent fictif (session), sans table users ni hachage de mot de passe — suffisant pour la démonstration, à remplacer par une authentification complète en production.
- **CSRF non appliqué sur l'API** : le groupe `api` n'impose pas le token CSRF (démonstration mono-usage) ; la session agent reste protégée par le middleware dédié.
- **Idempotency-Key non implémentée** pour la création : la répétition d'une création crée deux demandes. Implémentation possible en une table `idempotency_keys` + middleware, écartée pour rester dans le temps imparti. Les transitions, elles, sont déjà protégées.
- **Pas d'authentification usager** : le dépôt est public (comme l'énoncé le décrit) ; le code de suivi sert de justificatif côté usager.
- **Consultation publique par NPI** : volontairement retirée ; chaque usager suit sa demande grâce à son code.

## Sécurité

- Aucun secret dans le dépôt : `.env` est ignoré par Git, `.env.example` sert de modèle.
- Aucune donnée réelle : toutes les données de démonstration sont fictives.
