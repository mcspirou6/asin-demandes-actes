<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validation du changement de statut d'une demande.
 * Le motif de rejet n'est obligatoire que pour le statut "rejected".
 */
class UpdateRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(RequestStatus::class)],
            // Le caractère obligatoire du motif est contrôlé par le service
            // APRÈS la vérification de la transition : une transition
            // interdite doit renvoyer 409 même si le motif est absent.
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status' => 'Le statut est invalide.',
            'rejection_reason.max' => 'Le motif de rejet ne doit pas dépasser 500 caractères.',
        ];
    }
}
