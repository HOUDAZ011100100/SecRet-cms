<?php

namespace App\Services\Feedbacks;

use App\Models\Feedback;

/**
 * Objet de Transfert de Données (DTO) représentant le résultat d'une opération d'approbation d'avis.
 *
 * Cette classe encapsule le modèle Feedback mis à jour et un message descriptif
 * sur le résultat de l'opération, facilitant une communication claire entre
 * le FeedbackService et ses consommateurs (ex: Contrôleurs).
 */
readonly class FeedbackApprovalResult
{
    /**
     * @param  Feedback  $feedback  L'instance de l'avis mise à jour (approuvée ou rejetée).
     * @param  string  $message  Un message de succès ou de statut descriptif.
     */
    public function __construct(
        public Feedback $feedback,
        public string $message,
    ) {}
}
