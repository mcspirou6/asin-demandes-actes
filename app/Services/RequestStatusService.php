<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Request;
use Illuminate\Validation\ValidationException;

/**
 * Service métier responsable du cycle de vie des demandes.
 *
 * Toute la logique de transition est centralisée ici afin que les
 * contrôleurs restent minces et que la règle soit unique et testable.
 */
class RequestStatusService
{
    /**
     * Faire progresser une demande vers un nouveau statut.
     *
     * Contrôle effectué côté serveur : le client ne peut jamais imposer
     * une transition hors cycle de vie (409 Conflict si interdite).
     *
     * Idempotence : répéter la même requête (ex: processing -> approved
     * deux fois) est refusé avec 409, car un état final est figé et la
     * répétition ne doit jamais créer d'effet métier supplémentaire.
     */
    public function transition(Request $request, RequestStatus $target, ?string $rejectionReason = null): Request
    {
        $current = $request->status;

        // 1. Contrôle de la transition : toute transition hors cycle de vie
        //    est refusée en 409 Conflict, indépendamment des autres données.
        if (! $current->canTransitionTo($target)) {
            throw InvalidTransitionException::make($current, $target);
        }

        // 2. Règle métier : un rejet doit obligatoirement être motivé.
        //    (erreur de validation 422, contrairement à la transition
        //    interdite qui est une erreur métier 409)
        if ($target === RequestStatus::Rejected && trim((string) $rejectionReason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => ['Un rejet doit obligatoirement être motivé.'],
            ]);
        }

        // Le motif n'est enregistré que pour un rejet ; il est obligatoire
        // à ce stade (garanti par la couche de validation).
        $request->status = $target;
        $request->rejection_reason = $target === RequestStatus::Rejected ? $rejectionReason : null;
        $request->save();

        return $request;
    }
}
