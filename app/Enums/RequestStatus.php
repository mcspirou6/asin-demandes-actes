<?php

namespace App\Enums;

/**
 * Statuts du cycle de vie d'une demande :
 *
 * submitted -> processing -> approved
 *                        \-> rejected
 *
 * Une demande approuvée ou rejetée est un état final : plus aucune transition.
 */
enum RequestStatus: string
{
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Transitions autorisées depuis le statut courant.
     * Toute transition absente de cette liste est interdite (erreur métier 409).
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Processing],
            self::Processing => [self::Approved, self::Rejected],
            // États finaux : aucune transition possible.
            self::Approved, self::Rejected => [],
        };
    }

    /**
     * Indique si une transition vers $target est autorisée.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
