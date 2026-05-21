<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ApiException;
use App\Models\Registration;
use RuntimeException;

/**
 * Exception levée pour les erreurs liées aux inscriptions aux événements.
 *
 * Cela inclut les échecs lors du processus d'inscription, les problèmes de paiement,
 * ou les violations des contraintes d'inscription (par exemple, événement complet).
 */
class RegistrationException extends RuntimeException implements ApiException
{
    /**
     * @param  string  $message  Le message d'erreur.
     * @param  int  $status  Le code de statut HTTP.
     * @param  Registration|null  $registration  L'instance d'inscription associée à l'erreur, le cas échéant.
     */
    public function __construct(
        string $message,
        public readonly int $status = 422,
        public readonly ?Registration $registration = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Récupère le code de statut HTTP pour la réponse.
     */
    public function statusCode(): int
    {
        return $this->status;
    }

    /**
     * Récupère la charge utile de la réponse, incluant éventuellement les détails de l'inscription.
     *
     * @return array<string, mixed>
     */
    public function toResponsePayload(): array
    {
        $payload = ['message' => $this->getMessage()];

        if ($this->registration) {
            $payload['registration'] = $this->registration;
        }

        return $payload;
    }
}
