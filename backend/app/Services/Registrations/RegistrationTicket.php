<?php

namespace App\Services\Registrations;

/**
 * Objet de Transfert de Données (DTO) représentant un billet d'inscription.
 *
 * Cet objet contient les informations nécessaires pour générer ou présenter un billet
 * au participant, y compris le nom du fichier pour le téléchargement et la charge utile du billet.
 */
readonly class RegistrationTicket
{
    /**
     * @param  string  $filename  Nom de fichier suggéré pour le fichier du billet (ex: "ticket-123.json").
     * @param  array<string, mixed>  $payload  Les données du billet (code, titre de l'événement, nom du participant, etc.).
     */
    public function __construct(
        public string $filename,
        public array $payload,
    ) {}
}
