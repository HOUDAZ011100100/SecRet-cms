<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ApiException;
use RuntimeException;

/**
 * Exception levée pour les erreurs liées aux demandes d'événements (propositions).
 *
 * Cette exception peut transporter des données contextuelles supplémentaires pour aider le frontend
 * à comprendre la nature spécifique de l'échec.
 */
class EventRequestException extends RuntimeException implements ApiException
{
    /**
     * @param  string  $message  Le message d'erreur.
     * @param  int  $status  Le code de statut HTTP.
     * @param  array<string, mixed>  $context  Données supplémentaires liées à l'erreur.
     */
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly array $context = [],
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
     * Récupère la charge utile de la réponse, incluant le message d'erreur et tout contexte supplémentaire.
     *
     * @return array<string, mixed>
     */
    public function toResponsePayload(): array
    {
        return ['message' => $this->getMessage()] + $this->context;
    }
}
