<?php

namespace Database\Seeders;

use App\Enums\ActType;
use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Database\Seeder;

/**
 * Seeder de démonstration.
 *
 * Toutes les données sont FICTIVES (NPI générés pour la démonstration).
 * Après `php artisan migrate:fresh --seed`, la base contient :
 *  - des demandes avec des codes fixes pour deux usagers de démonstration ;
 *  - au moins une demande par statut (submitted, processing, approved,
 *    rejected) ;
 *  - au moins un exemple de chaque type d'acte ;
 *  - un motif pour chaque demande rejetée.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usager de démonstration utilisé aussi par le frontend.
        $demoNpi = '0123456789';

        // Demandes de l'usager de démonstration : couvre les 4 statuts
        // et les 3 types d'actes, avec dates échelonnées pour vérifier
        // le tri du plus récent au plus ancien. Les codes de suivi sont
        // fixes pour permettre une démonstration reproductible.
        $demoRequests = [
            [
                'tracking_code' => 'ASIN-DEMO01',
                'act_type' => ActType::BirthCertificate,
                'copies_count' => 2,
                'status' => RequestStatus::Submitted,
                'days_ago' => 1,
            ],
            [
                'tracking_code' => 'ASIN-DEMO02',
                'act_type' => ActType::CriminalRecord,
                'copies_count' => 1,
                'status' => RequestStatus::Processing,
                'days_ago' => 2,
            ],
            [
                'tracking_code' => 'ASIN-DEMO03',
                'act_type' => ActType::ResidenceCertificate,
                'copies_count' => 3,
                'status' => RequestStatus::Approved,
                'days_ago' => 3,
            ],
            [
                'tracking_code' => 'ASIN-DEMO04',
                'act_type' => ActType::BirthCertificate,
                'copies_count' => 1,
                'status' => RequestStatus::Rejected,
                'rejection_reason' => 'Les informations fournies sont incomplètes.',
                'days_ago' => 4,
            ],
            [
                'tracking_code' => 'ASIN-DEMO05',
                'act_type' => ActType::CriminalRecord,
                'copies_count' => 5,
                'status' => RequestStatus::Submitted,
                'days_ago' => 5,
            ],
        ];

        foreach ($demoRequests as $data) {
            Request::create([
                'tracking_code' => $data['tracking_code'],
                'npi' => $demoNpi,
                'act_type' => $data['act_type'],
                'copies_count' => $data['copies_count'],
                'status' => $data['status'],
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'created_at' => now()->subDays($data['days_ago']),
            ]);
        }

        Request::create([
            'tracking_code' => 'ASIN-DEMO06',
            'npi' => '0466170591',
            'act_type' => ActType::ResidenceCertificate,
            'copies_count' => 1,
            'status' => RequestStatus::Submitted,
            'created_at' => now(),
        ]);

        // Quelques demandes pour d'autres usagers fictifs, afin d'avoir
        // davantage de dossiers dans l'espace agent de démonstration.
        Request::factory()
            ->count(7)
            ->sequence(
                ['status' => RequestStatus::Submitted],
                ['status' => RequestStatus::Submitted],
                ['status' => RequestStatus::Processing],
                ['status' => RequestStatus::Approved],
                ['status' => RequestStatus::Approved],
                ['status' => RequestStatus::Rejected],
                ['status' => RequestStatus::Submitted],
                ['status' => RequestStatus::Processing],
            )
            ->create()
            ->each(function (Request $request) {
                // Un rejet doit toujours être motivé.
                if ($request->status === RequestStatus::Rejected) {
                    $request->update([
                        'rejection_reason' => 'Le justificatif de domicile fourni n\'est pas valide.',
                    ]);
                }
            });
    }
}
