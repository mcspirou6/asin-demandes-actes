<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestStatusRequest;
use App\Http\Resources\RequestResource;
use App\Models\Request;
use App\Services\RequestStatusService;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Str;

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
        // Le code de suivi est généré par le SERVEUR et remis à l'usager :
        // il lui permettra de suivre sa demande sans connaître le NPI.
        $request = Request::create([
            ...$httpRequest->validated(),
            'tracking_code' => $this->generateTrackingCode(),
            'status' => RequestStatus::Submitted,
        ]);

        return RequestResource::make($request)->response()->setStatusCode(201);
    }

    /**
     * Génère un code de suivi unique au format ASIN-XXXXXX.
     * La boucle couvre l'improbable collision (36^6 combinaisons).
     */
    private function generateTrackingCode(): string
    {
        do {
            $code = 'ASIN-' . Str::upper(Str::random(6));
        } while (Request::query()->where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Suivre une demande à partir de son code de suivi (public).
     * Sert l'écran "Suivre ma demande" de l'espace usager.
     */
    public function track(string $code): RequestResource
    {
        $request = Request::query()
            ->where('tracking_code', strtoupper(trim($code)))
            ->first();

        abort_unless(
            $request !== null,
            404,
            'Aucune demande ne correspond à ce code de suivi.'
        );

        return new RequestResource($request);
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
