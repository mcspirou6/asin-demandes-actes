<?php

namespace App\Enums;

/**
 * Types d'actes administratifs déposables par un usager.
 * Utilisé comme valeur d'Enum en base de données (string backing).
 */
enum ActType: string
{
    case BirthCertificate = 'birth_certificate';
    case CriminalRecord = 'criminal_record';
    case ResidenceCertificate = 'residence_certificate';

    /**
     * Libellé lisible, utilisé pour l'affichage côté frontend et la documentation.
     */
    public function label(): string
    {
        return match ($this) {
            self::BirthCertificate => 'Acte de naissance',
            self::CriminalRecord => 'Casier judiciaire',
            self::ResidenceCertificate => 'Certificat de résidence',
        };
    }
}
