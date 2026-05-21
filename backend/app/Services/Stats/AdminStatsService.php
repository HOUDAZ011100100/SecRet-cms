<?php

namespace App\Services\Stats;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\RegistrationStatsService;
use App\Support\Money;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection as MongoConnection;

/**
 * Service fournissant des statistiques de haut niveau et des données de tableau de bord pour les Administrateurs.
 *
 * Il agrège les données concernant les utilisateurs, les événements, les paiements et les demandes, offrant une
 * vue d'ensemble complète de l'activité de la plateforme.
 */
class AdminStatsService
{
    /**
     * @var array<string, string>
     */
    private const ROLE_RESPONSE_KEYS = [
        User::ROLE_ORGANIZER => 'organizer',
    ];

    /**
     * @param  RegistrationStatsService  $registrationStats  Service pour attacher les nombres d'inscriptions aux modèles d'événements.
     */
    public function __construct(private readonly RegistrationStatsService $registrationStats) {}

    /**
     * Agrège divers indicateurs dans une seule charge utile pour le tableau de bord d'administration.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'users_total' => User::count(),
            'users_by_role' => $this->usersByRole(),
            'events_total' => Event::count(),
            'events_published' => Event::where('status', Event::STATUS_PUBLISHED)->count(),
            'registrations_total' => Registration::count(),
            // Le revenu est calculé uniquement à partir des paiements terminés avec succès.
            'revenue' => Money::floatFromCents(Payment::where('status', 'completed')->sum('amount_cents')),
            'pending_requests' => EventRequest::where('status', EventRequest::STATUS_PENDING)->count(),
            'pending_publications' => Event::where('status', Event::STATUS_PENDING_PUBLICATION)->count(),
            'past_events' => $this->pastEvents(),
        ];
    }

    /**
     * Récupère une ventilation du nombre d'utilisateurs groupés par leurs rôles.
     *
     * Utilise le framework d'agrégation de MongoDB pour un comptage efficace sur l'ensemble de la collection.
     *
     * @return array<string, int> Carte des noms de rôles par rapport aux nombres d'utilisateurs.
     */
    private function usersByRole(): array
    {
        /** @var MongoConnection $connection */
        $connection = DB::connection('mongodb');

        $results = $connection
            ->getDatabase()
            ->selectCollection('users')
            ->aggregate([
                ['$group' => ['_id' => '$role', 'count' => ['$sum' => 1]]],
            ]);

        $counts = [];
        foreach ($results as $result) {
            $role = data_get($result, '_id');
            $key = $this->roleResponseKey((string) ($role ?? ''));
            $counts[$key] = ($counts[$key] ?? 0) + (int) data_get($result, 'count', 0);
        }

        return $counts;
    }

    private function roleResponseKey(string $role): string
    {
        return self::ROLE_RESPONSE_KEYS[$role] ?? $role;
    }

    /**
     * Récupère et formate une liste d'événements déjà terminés.
     *
     * @return list<array<string, mixed>>
     */
    private function pastEvents(): array
    {
        $events = Event::query()
            ->where('status', Event::STATUS_PUBLISHED)
            ->finished()
            ->with([
                'organizer:id,name',
                'eventRequest:id,title,contact_name,contact_email',
            ])
            ->get();

        $this->registrationStats->attachCount($events, 'tickets_count', 'paid');

        return $events
            ->sort(fn (Event $a, Event $b): int => $this->comparePastEvents($a, $b))
            ->values()
            ->map(fn (Event $event): array => $this->formatPastEvent($event))
            ->all();
    }

    /**
     * Logique de comparaison personnalisée pour trier les événements : date de fin la plus récente en premier.
     */
    private function comparePastEvents(Event $a, Event $b): int
    {
        $aEffectiveEnd = $this->timestamp($a->getAttribute('end_at') ?? $a->getAttribute('start_at'));
        $bEffectiveEnd = $this->timestamp($b->getAttribute('end_at') ?? $b->getAttribute('start_at'));

        if ($aEffectiveEnd === $bEffectiveEnd) {
            return $this->timestamp($b->getAttribute('start_at')) <=> $this->timestamp($a->getAttribute('start_at'));
        }

        return $bEffectiveEnd <=> $aEffectiveEnd;
    }

    /**
     * Normalise divers formats de date en un timestamp Unix.
     */
    private function timestamp(mixed $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? 0 : $timestamp;
    }

    /**
     * Formate un modèle Event en un tableau sérialisé pour les réponses API.
     *
     * @return array<string, mixed>
     */
    private function formatPastEvent(Event $event): array
    {
        $eventRequest = $event->relationLoaded('eventRequest') ? $event->getRelation('eventRequest') : null;
        $organizer = $event->relationLoaded('organizer') ? $event->getRelation('organizer') : null;

        return [
            'id' => $event->getKey(),
            'title' => $event->getAttribute('title'),
            'description' => $event->getAttribute('description'),
            'image_url' => $event->getAttribute('image_url'),
            'location' => $event->getAttribute('location'),
            'start_at' => $event->getAttribute('start_at'),
            'end_at' => $event->getAttribute('end_at'),
            'ticket_price' => (float) $event->getAttribute('ticket_price'),
            'registered_count' => $event->getAttribute('registered_count'),
            'capacity' => $event->getAttribute('capacity'),
            'tickets_count' => (int) $event->getAttribute('tickets_count'),
            'organizer' => $organizer,
            'event_request' => $eventRequest instanceof EventRequest ? [
                'contact_name' => $eventRequest->getAttribute('contact_name'),
                'contact_email' => $eventRequest->getAttribute('contact_email'),
            ] : null,
        ];
    }
}
