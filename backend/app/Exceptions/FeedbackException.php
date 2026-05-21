<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ApiException;
use RuntimeException;

/**
 * Exception levée pour les erreurs liées au système de feedback.
 *
 * Des exemples incluent la tentative de soumission d'un feedback pour un événement auquel l'utilisateur n'a pas participé,
 * ou la tentative de modification d'un feedback qui est déjà verrouillé.
 */
class FeedbackException extends RuntimeException implements ApiException
{
    /**
     * @param  string  $message  Le message d'erreur.
     * @param  int  $status  Le code de statut HTTP.
     */
    public function __construct(
        string $message,
        private readonly int $status = 422,
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
     * Récupère la représentation de la charge utile de réponse de l'exception.
     *
     * @return array<string, mixed>
     */
    public function toResponsePayload(): array
    {
        return ['message' => $this->getMessage()];
    }
}
