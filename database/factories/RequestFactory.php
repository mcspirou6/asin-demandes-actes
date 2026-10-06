<?php

namespace Database\Factories;

use App\Enums\ActType;
use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory de démonstration et de test pour les demandes.
 * Tous les NPI générés sont fictifs.
 */
class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            // NPI fictif : commence par 0 volontairement pour vérifier
            // que les zéros initiaux sont préservés (stockage string).
            'npi' => '0' . $this->faker->numberBetween(100000000, 999999999),
            'act_type' => $this->faker->randomElement(array_column(ActType::cases(), 'value')),
            'copies_count' => $this->faker->numberBetween(1, 5),
            'status' => RequestStatus::Submitted,
            'rejection_reason' => null,
            'created_at' => now(),
        ];
    }

    public function npi(string $npi): static
    {
        return $this->state(fn () => ['npi' => $npi]);
    }

    public function actType(ActType $type): static
    {
        return $this->state(fn () => ['act_type' => $type]);
    }

    public function status(RequestStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function rejected(string $reason): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }
}
