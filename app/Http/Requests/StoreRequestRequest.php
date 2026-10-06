<?php

namespace App\Http\Requests;

use App\Enums\ActType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validation de la création d'une demande.
 * Toute donnée client est considérée comme non fiable.
 */
class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Exactement 10 chiffres ; stocké en string pour préserver les zéros initiaux.
            'npi' => ['required', 'string', 'digits:10'],
            'act_type' => ['required', new Enum(ActType::class)],
            // Entier entre 1 et 5 inclus. "integer" rejette "2.5" et "abc".
            'copies_count' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * Messages en français pour une lecture claire par l'usager/agent.
     */
    public function messages(): array
    {
        return [
            'npi.required' => 'Le NPI est obligatoire.',
            'npi.digits' => 'Le NPI doit comporter exactement 10 chiffres.',
            'act_type.required' => 'Le type d\'acte est obligatoire.',
            'act_type' => 'Le type d\'acte est invalide.',
            'copies_count.required' => 'Le nombre de copies est obligatoire.',
            'copies_count.integer' => 'Le nombre de copies doit être un entier.',
            'copies_count.min' => 'Le nombre de copies doit être compris entre 1 et 5.',
            'copies_count.max' => 'Le nombre de copies doit être compris entre 1 et 5.',
        ];
    }
}
