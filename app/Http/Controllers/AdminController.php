<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Resources\RequestResource;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

/**
 * Espace agent (validateur) : connexion, liste globale des demandes.
 *
 * Authentification volontairement simple : un unique agent fictif,
 * identifiants de démonstration dans config/admin.php. La session
 * marque l'agent comme connecté ; le middleware "admin" protège
 * ensuite les endpoints de traitement.
 */
class AdminController extends Controller
{
    public function login(HttpRequest $httpRequest): JsonResponse
    {
        $data = $httpRequest->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email est invalide.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        // hash_equals : comparaison à durée constante, bonne pratique même
        // sur des identifiants de démonstration.
        $emailOk = hash_equals(config('admin.email'), strtolower(trim($data['email'])));
        $passwordOk = hash_equals(config('admin.password'), $data['password']);

        if (! $emailOk || ! $passwordOk) {
            // Message volontairement générique : ne pas indiquer si
            // c'est l'email ou le mot de passe qui est faux.
            return response()->json(['message' => 'Identifiants incorrects.'], 422);
        }

        $httpRequest->session()->regenerate();
        $httpRequest->session()->put('admin_authenticated', true);

        return response()->json(['message' => 'Connexion réussie.']);
    }

    /**
     * Indique au frontend si une session agent est active (au chargement).
     */
    public function me(HttpRequest $httpRequest): JsonResponse
    {
        return response()->json([
            'authenticated' => (bool) $httpRequest->session()->get('admin_authenticated'),
        ]);
    }

    public function logout(HttpRequest $httpRequest): JsonResponse
    {
        $httpRequest->session()->forget('admin_authenticated');
        $httpRequest->session()->invalidate();

        return response()->json(['message' => 'Déconnexion effectuée.']);
    }

    /**
     * Vue agent : TOUTES les demandes, filtrables par statut et par NPI,
     * paginées de la plus récente à la plus ancienne.
     */
    public function index(HttpRequest $httpRequest): AnonymousResourceCollection
    {
        $httpRequest->validate([
            'status' => ['nullable', new Enum(RequestStatus::class)],
            'npi' => ['nullable', 'digits:10'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50'],
        ], [
            'status' => 'Le statut de filtrage est invalide.',
            'npi' => 'Le NPI recherché doit comporter exactement 10 chiffres.',
            'per_page.in' => 'Le nombre de demandes par page doit être 10, 20 ou 50.',
        ]);

        $requests = Request::query()
            ->when(
                $httpRequest->filled('status'),
                fn ($query) => $query->where('status', $httpRequest->enum('status', RequestStatus::class))
            )
            ->when(
                $httpRequest->filled('npi'),
                fn ($query) => $query->where('npi', $httpRequest->input('npi'))
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(perPage: (int) $httpRequest->input('per_page', 20))
            ->withQueryString();

        return RequestResource::collection($requests);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'submitted' => Request::query()
                ->where('status', RequestStatus::Submitted)
                ->count(),
        ]);
    }
}
