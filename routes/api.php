<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API des demandes d'actes
|--------------------------------------------------------------------------
*/

// Déposer une demande d'acte administratif.
Route::post('/requests', [RequestController::class, 'store']);

// Statistiques globales (nombre de demandes par statut).
// Déclarée avant le préfixe {npi} pour éviter toute ambiguïté.
Route::get('/requests/stats', [RequestController::class, 'stats']);

// Lister les demandes d'un usager (tri décroissant, filtre par statut facultatif).
Route::get('/users/{npi}/requests', [RequestController::class, 'index']);

// Faire avancer le traitement d'une demande (cycle de vie contrôlé côté serveur).
Route::patch('/requests/{request}/status', [RequestController::class, 'updateStatus']);

// Assistant conversationnel : réponses prédéfinies (config/chatbot.php), sans IA.
Route::get('/chatbot', [ChatbotController::class, 'answer']);
