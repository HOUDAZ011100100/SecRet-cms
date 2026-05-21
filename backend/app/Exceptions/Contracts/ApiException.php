<?php

namespace App\Exceptions\Contracts;

/**
 * Interface pour les exceptions qui doivent être rendues sous forme de réponses JSON API.
 *
 * Les exceptions implémentant ce contrat fournissent leurs propres codes de statut HTTP
 * et charges utiles de réponse, permettant un reporting d'erreurs cohérent dans toute l'API.
 */
interface ApiException
{
    /**
     * Récupère le code de statut HTTP pour la réponse d'erreur.
     */
    public function statusCode(): int;

    /**
     * Récupère la structure de données à renvoyer dans le corps de la réponse JSON.
     *
     * @return array<string, mixed>
     */
    public function toResponsePayload(): array;
}
