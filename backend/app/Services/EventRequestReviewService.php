<?php

namespace App\Services;

use App\Exceptions\EventRequestReviewException;
use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service gérant le flux de modération des demandes d'événements (Event Requests).
 *
 * Ce service gère la transition d'une demande de l'état "en attente" (pending) vers "approuvée" (approved)
 * ou "rejetée" (rejected). Les demandes approuvées déclenchent automatiquement la création d'un projet d'événement
 * (Event draft) correspondant. L'opération de révision est intentionnellement centralisée ici car l'approbation
 * modifie deux collections : la demande originale et, en cas d'approbation, le nouvel événement.
 */
class EventRequestReviewService
{
    /**
     * Rejette une demande d'événement avec un motif optionnel.
     *
     * @param  EventRequest  $eventRequest  La demande à rejeter.
     * @param  User  $reviewer  L'administrateur effectuant la révision.
     * @param  string|null  $reason  Commentaire optionnel expliquant pourquoi la demande a été rejetée.
     * @return EventRequest L'instance de la demande mise à jour.
     *
     * @throws EventRequestReviewException Si le réviseur n'est pas autorisé ou si la demande a déjà été traitée.
     */
    public function reject(EventRequest $eventRequest, User $reviewer, ?string $reason): EventRequest
    {
        $this->ensureReviewerIsAdmin($reviewer);

        // Le rejet conserve la demande comme historique d'audit et enregistre qui a pris la décision.
        $reviewedRequest = $this->markReviewed($eventRequest, [
            'status' => EventRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by_id' => $reviewer->getKey(),
        ]);

        // Le client doit être notifié car les demandes rejetées ne créent pas d'enregistrement d'événement.
        NotificationService::eventRequestReviewed($reviewedRequest, EventRequest::STATUS_REJECTED);

        return $reviewedRequest;
    }

    /**
     * Approuve une demande d'événement et génère un nouveau projet d'événement.
     *
     * @param  EventRequest  $eventRequest  La demande à approuver.
     * @param  User  $reviewer  L'administrateur effectuant la révision.
     * @return array{event_request: EventRequest, event: Event}
     *
     * @throws EventRequestReviewException Si le réviseur n'est pas autorisé ou si la demande a déjà été traitée.
     */
    public function approve(EventRequest $eventRequest, User $reviewer): array
    {
        $this->ensureReviewerIsAdmin($reviewer);

        // L'approbation met à jour la demande et crée un événement ; les deux changements doivent être validés ensemble.
        return DB::transaction(function () use ($eventRequest, $reviewer) {
            // La mise à jour conditionnelle dans markReviewed empêche les conflits de double approbation/rejet.
            $reviewedRequest = $this->markReviewed($eventRequest, [
                'status' => EventRequest::STATUS_APPROVED,
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by_id' => $reviewer->getKey(),
            ]);

            // Les dates préférées du client sont optionnelles dans les anciennes données/données de démo,
            // l'approbation a donc des valeurs par défaut sûres.
            $start = $reviewedRequest->getAttribute('preferred_start');
            if (! $start instanceof Carbon) {
                $start = now()->addWeek();
            }

            $end = $reviewedRequest->getAttribute('preferred_end');
            if (! $end instanceof Carbon) {
                $end = $start->copy()->addHours(4);
            }

            // Les demandes approuvées deviennent des projets d'événements afin que le personnel puisse encore
            // assigner un organisateur et affiner les détails.
            $event = Event::create([
                'event_request_id' => $reviewedRequest->getKey(),
                'organizer_id' => null, // À assigner plus tard par un administrateur.
                'created_by' => $reviewer->getKey(),
                'title' => $reviewedRequest->getAttribute('title'),
                'description' => $reviewedRequest->getAttribute('description'),
                'image_path' => $reviewedRequest->getAttribute('image_path'),
                'location' => $reviewedRequest->getAttribute('location'),
                'start_at' => $start,
                'end_at' => $end,
                'capacity' => 100, // Capacité par défaut, peut être ajustée.
                'registered_count' => 0,
                'ticket_price' => $reviewedRequest->getAttribute('ticket_price') ?? 0,
                'status' => Event::STATUS_DRAFT,
            ]);

            // Notifier après que l'événement existe pour que les liens frontend puissent pointer vers l'enregistrement créé si nécessaire.
            NotificationService::eventRequestReviewed($reviewedRequest, EventRequest::STATUS_APPROVED);

            return [
                'event_request' => $reviewedRequest,
                'event' => $event->load('eventRequest'),
            ];
        });
    }

    /**
     * Marque atomiquement une demande comme révisée si elle est toujours en statut "en attente" (pending).
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws EventRequestReviewException Si la demande a déjà été traitée par quelqu'un d'autre.
     */
    private function markReviewed(EventRequest $eventRequest, array $attributes): EventRequest
    {
        // C'est le verrou du flux de travail : seule une demande toujours en attente peut être révisée.
        $updated = EventRequest::query()
            ->whereKey($eventRequest->getKey())
            ->where('status', EventRequest::STATUS_PENDING)
            ->update($attributes);

        if (! $updated) {
            throw new EventRequestReviewException('Cette demande a déjà été traitée.');
        }

        $eventRequest->refresh();

        return $eventRequest;
    }

    /**
     * Applique l'autorisation administrative.
     *
     * @throws EventRequestReviewException
     */
    private function ensureReviewerIsAdmin(User $reviewer): void
    {
        if (! $reviewer->isAdmin()) {
            throw new EventRequestReviewException('Accès refusé pour ce rôle.', 403);
        }
    }
}
