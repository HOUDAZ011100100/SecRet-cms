<?php

namespace App\Services\Feedbacks;

use App\Exceptions\FeedbackException;
use App\Models\Event;
use App\Models\EventRequest;
use App\Models\Feedback;
use App\Models\Registration;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;

class FeedbackService
{
    /** @return Collection<int, Feedback> */
    public function listForEvent(User $viewer, Event $event): Collection
    {
        $this->ensureEventIsVisibleTo($viewer, $event);
        $this->ensureCanViewFeedbacks($viewer, $event);

        $query = Feedback::query()
            ->where('event_id', $event->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc');

        if (! $viewer->isAdmin()) {
            $query->where('status', Feedback::STATUS_APPROVED);
        }

        return $query->get();
    }

    /** @param array{rating: int, comment?: string|null} $data */
    public function submit(User $participant, Event $event, array $data): Feedback
    {
        if ($participant->getAttribute('role') !== User::ROLE_PARTICIPANT) {
            throw new FeedbackException('This action is unauthorized.', 403);
        }

        if ($event->getAttribute('status') !== Event::STATUS_PUBLISHED) {
            throw new FeedbackException('Événement non disponible.');
        }

        if (! $this->participantHasPaidRegistration($participant, $event)) {
            throw new FeedbackException('Inscription payante requise pour laisser un avis.', 403);
        }

        $feedback = Feedback::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $participant->id,
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => Feedback::STATUS_PENDING,
            ],
        );

        $feedback->load('user:id,name', 'event');

        NotificationService::feedbackSubmitted($feedback);

        return $feedback;
    }

    public function approve(User $reviewer, Feedback $feedback): FeedbackApprovalResult
    {
        if (! $reviewer->isAdmin()) {
            throw new FeedbackException('This action is unauthorized.', 403);
        }

        if ($feedback->getAttribute('status') === Feedback::STATUS_APPROVED) {
            return new FeedbackApprovalResult(
                $feedback->load('user:id,name'),
                'Cet avis est déjà publié.',
            );
        }

        $feedback->update(['status' => Feedback::STATUS_APPROVED]);
        $feedback->load('user:id,name', 'event');

        NotificationService::feedbackApproved($feedback);

        return new FeedbackApprovalResult($feedback, 'Avis publié.');
    }

    public function delete(User $reviewer, Feedback $feedback): void
    {
        if (! $reviewer->isAdmin()) {
            throw new FeedbackException('This action is unauthorized.', 403);
        }

        $feedback->delete();
    }

    private function ensureEventIsVisibleTo(User $viewer, Event $event): void
    {
        if ($event->getAttribute('status') === Event::STATUS_PUBLISHED || $this->canManageEvent($viewer, $event)) {
            return;
        }

        throw new FeedbackException('Not found.', 404);
    }

    private function ensureCanViewFeedbacks(User $viewer, Event $event): void
    {
        $event->loadMissing(['creator:id,role', 'eventRequest']);

        if ($viewer->isAdmin()) {
            return;
        }

        if (
            $viewer->getAttribute('role') === User::ROLE_PARTICIPANT
            && $event->getAttribute('status') === Event::STATUS_PUBLISHED
        ) {
            return;
        }

        if ($viewer->getAttribute('role') === User::ROLE_CLIENT && $this->clientOwnsEvent($viewer, $event)) {
            return;
        }

        $creator = $event->getRelation('creator');

        if (
            $viewer->getAttribute('role') === User::ROLE_ORGANIZER
            && $creator instanceof User
            && $creator->getAttribute('role') === User::ROLE_ORGANIZER
        ) {
            if ($event->isOrganizer($viewer)) {
                return;
            }
        }

        throw new FeedbackException('This action is unauthorized.', 403);
    }

    private function canManageEvent(User $viewer, Event $event): bool
    {
        if ($viewer->isAdmin()) {
            return true;
        }

        return $event->isOrganizer($viewer);
    }

    private function clientOwnsEvent(User $viewer, Event $event): bool
    {
        $event->loadMissing('eventRequest');
        $eventRequest = $event->getRelation('eventRequest');

        return $eventRequest instanceof EventRequest
            && strcasecmp($eventRequest->getAttribute('contact_email'), $viewer->getAttribute('email')) === 0;
    }

    private function participantHasPaidRegistration(User $participant, Event $event): bool
    {
        return Registration::where('event_id', $event->id)
            ->where('user_id', $participant->id)
            ->where('payment_status', 'paid')
            ->exists();
    }
}
