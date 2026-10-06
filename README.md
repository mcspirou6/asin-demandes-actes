# ASIN — Suivi des demandes d'actes

Étude de cas pratique — **Développeur(se) junior(e)** — Agence des Systèmes d'Information et du Numérique (ASIN), République du Bénin.

Application de gestion des demandes d'actes administratifs (acte de naissance, casier judiciaire, certificat de résidence) : API Laravel + interface de consultation Vue.js.

- **Candidat(e) :** Toyin Spéro Mériem AKPO

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
2. une **interface de consultation** (Vue.js) affichant les demandes d'un usager ;
3. une **suite de tests automatisés** couvrant toutes les règles de gestion.

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
git clone <url-du-depot>
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
- 8 demandes supplémentaires pour d'autres usagers fictifs (utiles pour les statistiques) ;
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

- **Interface de consultation :** http://127.0.0.1:8000
- **API :** http://127.0.0.1:8000/api/...

> En développement, `npm run dev` fonctionne aussi : Vite relaie les appels `/api` vers le port 8000 (proxy configuré dans `vite.config.js`).

Pour la recette, la recherche du NPI **`0123456789`** dans l'interface affiche immédiatement les demandes de démonstration.

## Tests

```bash
php artisan test
```

37 tests couvrent : création et validation (NPI, type d'acte, copies), liste et tri, filtre par statut, cycle de vie, transitions interdites, rejet motivé, idempotence, pagination et statistiques.

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
- Le statut initial `submitted` est **posé par le serveur** : le client ne peut pas l'imposer.

Réponse : `201 Created` avec la demande créée. Erreurs : `422 Unprocessable Entity`.

### GET /users/{npi}/requests — demandes d'un usager

- Tri : `created_at DESC` (plus récente → plus ancienne), départage par `id DESC`.
- Filtre facultatif : `?status=processing` (valeur invalide → `422`).
- Pagination : 20 demandes maximum par page (`?page=1`). Réponse paginée Laravel (métadonnées dans `meta`).

### PATCH /requests/{id}/status — faire avancer le traitement

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

### GET /requests/stats — statistiques (Bonus 2)

```json
{
    "submitted": 10,
    "processing": 5,
    "approved": 20,
    "rejected": 3
}
```

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
| 1 — Pagination (20 max par page) | ✅ |
| 2 — Nombre de demandes par statut (`GET /api/requests/stats` + affichage dans l'interface) | ✅ |
| 3 — Tests automatisés des règles de gestion (37 tests) | ✅ |
| 4 — Écran de consultation des demandes d'un usager (recherche par NPI, filtre, pagination) | ✅ |

## Interface

L'interface (http://127.0.0.1:8000) permet de :

- rechercher les demandes d'un usager par **NPI** ;
- **filtrer par statut** ;
- consulter type d'acte, nombre de copies, statut, date de dépôt, motif de rejet ;
- naviguer entre les pages ;
- interroger l'**assistant conversationnel** (voir ci-dessous).

Le style suit la référence visuelle **ANIP** : en-tête bleu nuit avec barre tricolore, fond bleu-gris clair, cartes blanches à bord fin avec pastilles d'icônes, accents orange pour les actions principales. Icônes SVG uniquement, aucun emoji.

## Assistant conversationnel (fonctionnalité additionnelle)

Une bulle de chat orange (en bas à droite, comme sur le site ANIP) ouvre un mini assistant qui répond aux questions sur le traitement des demandes.

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
│   │   ├── ChatbotController.php
│   │   └── RequestController.php
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

- **Idempotency-Key non implémentée** pour la création : la répétition d'une création crée deux demandes. Implémentation possible en une table `idempotency_keys` + middleware, écartée pour rester dans le temps imparti. Les transitions, elles, sont déjà protégées.
- **Pas d'authentification** : non demandée par l'énoncé ; l'API est ouverte (usage de démonstration).
- **Pas de création de demandes depuis l'interface** : l'énoncé demande un écran de *consultation* (Bonus 4) ; le dépôt se fait via l'API.
- **Recherche par NPI non indexée côté interface** : l'index `npi + created_at` est en base ; aucune recherche floue (non demandée).

## Sécurité

- Aucun secret dans le dépôt : `.env` est ignoré par Git, `.env.example` sert de modèle.
- Aucune donnée réelle : toutes les données de démonstration sont fictives.
