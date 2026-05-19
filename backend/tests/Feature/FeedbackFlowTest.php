<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\RefreshMongoDatabase;
use Tests\TestCase;

class FeedbackFlowTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_paid_participant_submits_feedback_as_pending_and_admin_approves_it(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $participant = $this->user(User::ROLE_PARTICIPANT);
        $event = $this->publishedEvent();
        $this->registration($participant, $event, ['payment_status' => 'paid', 'paid_at' => now()]);

        Sanctum::actingAs($participant);

        $this->postJson("/api/events/{$event->id}/feedback", [
            'rating' => 5,
            'comment' => 'Excellent session.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.user.id', $participant->id)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.status', Feedback::STATUS_PENDING)
            ->assertJsonPath('message', 'Votre avis a bien été envoyé. Il sera visible après validation par notre équipe.');

        $feedback = Feedback::query()->firstOrFail();

        $this->assertSame(1, Feedback::query()->count());
        $this->assertSame($participant->id, $feedback->user_id);
        $this->assertTrue(AppNotification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'admin_feedback_received')
            ->exists());

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/feedbacks/{$feedback->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', Feedback::STATUS_APPROVED)
            ->assertJsonPath('message', 'Avis publié.');

        $this->assertSame(Feedback::STATUS_APPROVED, $feedback->fresh()->status);
        $this->assertTrue(AppNotification::query()
            ->where('user_id', $participant->id)
            ->where('type', 'participant_feedback_approved')
            ->exists());

        $this->postJson("/api/admin/feedbacks/{$feedback->id}/approve")
            ->assertOk()
            ->assertJsonPath('message', 'Cet avis est déjà publié.');
    }

    public function test_participant_without_paid_registration_cannot_submit_feedback(): void
    {
        $participant = $this->user(User::ROLE_PARTICIPANT);
        $event = $this->publishedEvent();
        $this->registration($participant, $event, ['payment_status' => 'pending']);

        Sanctum::actingAs($participant);

        $this->postJson("/api/events/{$event->id}/feedback", [
            'rating' => 4,
            'comment' => 'Looks useful.',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Inscription payante requise pour laisser un avis.');

        $this->assertSame(0, Feedback::query()->count());
    }

    public function test_feedback_index_hides_pending_feedback_from_participants_and_shows_it_to_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $participant = $this->user(User::ROLE_PARTICIPANT);
        $reviewer = $this->user(User::ROLE_PARTICIPANT);
        $event = $this->publishedEvent();

        $this->feedback($reviewer, $event, [
            'status' => Feedback::STATUS_APPROVED,
            'rating' => 5,
            'comment' => 'Visible review.',
        ]);
        $this->feedback($participant, $event, [
            'status' => Feedback::STATUS_PENDING,
            'rating' => 2,
            'comment' => 'Pending review.',
        ]);

        Sanctum::actingAs($participant);

        $this->getJson("/api/events/{$event->id}/feedbacks")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', Feedback::STATUS_APPROVED)
            ->assertJsonPath('data.0.comment', 'Visible review.');

        Sanctum::actingAs($admin);

        $this->getJson("/api/events/{$event->id}/feedbacks")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_feedback_requires_valid_rating(): void
    {
        $participant = $this->user(User::ROLE_PARTICIPANT);
        $event = $this->publishedEvent();
        $this->registration($participant, $event, ['payment_status' => 'paid', 'paid_at' => now()]);

        Sanctum::actingAs($participant);

        $this->postJson("/api/events/{$event->id}/feedback", [
            'rating' => 6,
            'comment' => 'Too high.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');

        $this->assertSame(0, Feedback::query()->count());
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /** @param array<string, mixed> $overrides */
    private function publishedEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Security Feedback Forum',
            'description' => 'A practical session for feedback workflow testing.',
            'location' => 'Casablanca',
            'room' => 'Main Hall',
            'start_at' => Carbon::now()->addDays(10),
            'end_at' => Carbon::now()->addDays(10)->addHours(4),
            'capacity' => 100,
            'registered_count' => 0,
            'ticket_price' => 20,
            'status' => Event::STATUS_PUBLISHED,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function registration(User $participant, Event $event, array $overrides = []): Registration
    {
        return Registration::create(array_merge([
            'event_id' => $event->id,
            'user_id' => $participant->id,
            'status' => 'registered',
            'payment_status' => 'paid',
            'ticket_code' => 'ticket-'.$participant->id,
            'amount' => $event->ticket_price,
            'registered_at' => now(),
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function feedback(User $participant, Event $event, array $overrides = []): Feedback
    {
        return Feedback::create(array_merge([
            'event_id' => $event->id,
            'user_id' => $participant->id,
            'rating' => 4,
            'comment' => 'Solid event.',
            'status' => Feedback::STATUS_PENDING,
        ], $overrides));
    }
}
