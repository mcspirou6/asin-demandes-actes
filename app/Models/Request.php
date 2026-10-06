<?php

namespace App\Models;

use App\Enums\ActType;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle représentant une demande d'acte administratif.
 *
 * NB : le nom "Request" peut prêter à confusion avec Illuminate\Http\Request ;
 * toute utilisation de la requête HTTP doit passer par son FQCN complet.
 */
class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code',
        'npi',
        'act_type',
        'copies_count',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'act_type' => ActType::class,
        'status' => RequestStatus::class,
    ];
}
