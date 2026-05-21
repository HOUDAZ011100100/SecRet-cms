<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ApiException;
use RuntimeException;

/**
 * Exception levée lorsqu'une défaillance survient lors du calcul ou de l'accès aux statistiques.
 *
 * Généralement utilisée pour signaler des refus d'accès à certaines mesures ou des échecs
 * dans l'agrégation des données d'inscription.
 */
class StatsException extends RuntimeException implements ApiException
{
    /**
     * @param  string  $message  Le message d'erreur.
     * @param  int  $status  Le code de statut HTTP (par défaut 403 Forbidden).
     */
    public function __construct(
        string $message,
        private readonly int $status = 403,
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
