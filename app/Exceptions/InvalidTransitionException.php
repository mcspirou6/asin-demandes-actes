<?php

namespace App\Exceptions;

use App\Enums\RequestStatus;
use Exception;

/**
 * Exception métier levée lorsqu'une transition de statut n'est pas
 * autorisée par le cycle de vie (ex: submitted -> approved).
 * Convertie en réponse HTTP 409 par le gestionnaire d'exceptions.
 */
class InvalidTransitionException extends Exception
{
    public static function make(RequestStatus $from, RequestStatus $to): self
    {
        return new self(
            "La transition de {$from->value} vers {$to->value} est interdite."
        );
    }
}
