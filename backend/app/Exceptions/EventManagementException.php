<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ApiException;
use RuntimeException;

/**
 * Exception levée pour les erreurs générales de gestion d'événements.
 *
 * Utilisée lors de l'exécution d'opérations sur des événements qui échouent aux vérifications de la logique métier,
 * comme la mise à jour d'un événement terminé ou des transitions de statut invalides.
 */
class EventManagementException extends RuntimeException implements ApiException
{
    /**
     * @param  string  $message  Le message d'erreur.
     * @param  int  $status  Le code de statut HTTP.
     */
    public function __construct(
        string $message,
        public readonly int $status = 422,
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
