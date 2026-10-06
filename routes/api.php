<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API des demandes d'actes
|--------------------------------------------------------------------------
*/

// ---- Espace usager (public) ----

// Déposer une demande d'acte administratif (renvoie un code de suivi).
Route::post('/requests', [RequestController::class, 'store']);

// Statistiques globales (nombre de demandes par statut).
Route::get('/requests/stats', [RequestController::class, 'stats']);

// Suivre une demande à partir de son code de suivi.
Route::get('/requests/track/{code}', [RequestController::class, 'track']);

// Lister les demandes d'un usager (tri décroissant, filtre par statut facultatif).
Route::get('/users/{npi}/requests', [RequestController::class, 'index']);

// Assistant conversationnel : réponses prédéfinies, sans IA.
Route::get('/chatbot', [ChatbotController::class, 'answer']);

// ---- Espace agent (validateur) ----

// Connexion / état de session / déconnexion de l'agent.
Route::post('/admin/login', [AdminController::class, 'login']);
Route::get('/admin/me', [AdminController::class, 'me']);
Route::post('/admin/logout', [AdminController::class, 'logout']);

// Vue agent : toutes les demandes, filtrables, réservées à l'agent connecté.
Route::get('/admin/requests', [AdminController::class, 'index'])->middleware('admin');

// Faire avancer le traitement d'une demande : réservé à l'agent connecté.
// Le cycle de vie reste contrôlé côté serveur (409 si transition interdite).
Route::patch('/requests/{request}/status', [RequestController::class, 'updateStatus'])
    ->middleware('admin');
