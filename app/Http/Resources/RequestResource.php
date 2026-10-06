<?php

namespace App\Http\Resources;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON normalisée d'une demande.
 * Le type d'acte est renvoyé en valeur brute (Enum) + libellé lisible.
 */
class RequestResource extends JsonResource
{
    public function toArray(HttpRequest $request): array
    {
        $data = [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'npi' => $this->npi,
            'act_type' => $this->act_type->value,
            'act_type_label' => $this->act_type->label(),
            'copies_count' => $this->copies_count,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];

        if (! $request->session()->get('admin_authenticated')) {
            unset($data['npi']);
        }

        return $data;
    }
}
