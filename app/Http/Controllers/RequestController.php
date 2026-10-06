<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestStatusRequest;
use App\Http\Resources\RequestResource;
use App\Models\Request;
use App\Services\RequestStatusService;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

/**
 * Contrôleur API des demandes.
 *
 * Volontairement mince : la logique métier (cycle de vie) est déléguée
 * à App\Services\RequestStatusService, la validation aux FormRequests.
 */
class RequestController extends Controller
{
    public function __construct(
        private readonly RequestStatusService $statusService,
    ) {
    }

    /**
     * Déposer une demande. Le statut initial "submitted" est défini par le
     * serveur : le client ne peut jamais l'imposer.
     */
    public function store(StoreRequestRequest $httpRequest): \Illuminate\Http\JsonResponse
    {
        $request = Request::create([
            ...$httpRequest->validated(),
            'status' => RequestStatus::Submitted,
        ]);

        return RequestResource::make($request)->response()->setStatusCode(201);
    }

    /**
     * Lister les demandes d'un usager, de la plus récente à la plus ancienne.
     *
     * Filtre facultatif par statut (?status=...) : une valeur invalide est
     * rejetée en 422. Pagination : 20 maximum par page (Bonus 1), le client
     * ne peut pas demander arbitrairement un grand nombre de résultats.
     */
    public function index(HttpRequest $httpRequest, string $npi): AnonymousResourceCollection
    {
        $httpRequest->validate([
            'status' => ['nullable', new Enum(RequestStatus::class)],
            'page' => ['integer', 'min:1'],
        ], [
            'status' => 'Le statut de filtrage est invalide.',
            'page.integer' => 'Le numéro de page doit être un entier.',
            'page.min' => 'Le numéro de page doit être au moins 1.',
        ]);

        $requests = Request::query()
            ->where('npi', $npi)
            ->when(
                $httpRequest->filled('status'),
                fn ($query) => $query->where('status', $httpRequest->enum('status', RequestStatus::class))
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id') // départage stable si created_at identiques
            ->paginate(perPage: 20)
            ->withQueryString();

        return RequestResource::collection($requests);
    }

    /**
     * Statistiques : nombre de demandes par statut (Bonus 2).
     */
    public function stats(): array
    {
        return [
            'submitted' => Request::where('status', RequestStatus::Submitted)->count(),
            'processing' => Request::where('status', RequestStatus::Processing)->count(),
            'approved' => Request::where('status', RequestStatus::Approved)->count(),
            'rejected' => Request::where('status', RequestStatus::Rejected)->count(),
        ];
    }

    /**
     * Faire avancer le traitement d'une demande.
     * Le contrôle de transition et l'erreur 409 sont gérés par le service.
     */
    public function updateStatus(UpdateRequestStatusRequest $httpRequest, Request $request): RequestResource
    {
        $validated = $httpRequest->validated();

        $request = $this->statusService->transition(
            $request,
            RequestStatus::from($validated['status']),
            $validated['rejection_reason'] ?? null,
        );

        return new RequestResource($request);
    }
}
