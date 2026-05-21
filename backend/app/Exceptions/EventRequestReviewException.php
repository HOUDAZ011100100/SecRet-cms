<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ApiException;
use RuntimeException;

/**
 * Exception levée lorsqu'une défaillance survient lors de la révision d'une demande d'événement.
 *
 * Cette exception est généralement soulevée lorsqu'un administrateur tente d'approuver
 * ou de rejeter une demande d'événement mais viole une règle métier (par exemple, tenter d'approuver
 * une demande qui est déjà traitée).
 */
class EventRequestReviewException extends RuntimeException implements ApiException
{
    /**
     * @param  string  $message  Le message d'erreur.
     * @param  int  $status  Le code de statut HTTP (par défaut 422 Unprocessable Entity).
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
