<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\AssignEventOrganizerRequest;
use App\Http\Requests\Events\EventIndexRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventCapacityRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\EventManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des événements.
 *
 * Ce contrôleur gère le cycle de vie des événements, y compris la création, les mises à jour,
 * les flux de publication et la navigation des participants.
 * Le contrôle d'accès est un mélange de vérifications au niveau du contrôleur et de logique au niveau du service.
 */
class EventController extends Controller
{
    public const STATUS_PENDING_PUBLICATION = Event::STATUS_PENDING_PUBLICATION;

    /**
     * @param  EventManagementService  $events  Service pour la logique métier des événements.
     */
    public function __construct(private readonly EventManagementService $events) {}

    /**
     * Lister tous les événements (vue Administrateur).
     *
     * Fournit une liste paginée de tous les événements avec leurs organisateurs et créateurs.
     * Prend en charge la recherche par titre, description ou lieu.
     *
     * @return JsonResponse Liste paginée d'événements.
     */
    public function indexAll(EventIndexRequest $request)
    {
        $q = Event::query()
            ->with(['organizer', 'eventRequest', 'creator:id,name,role'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->validated('q')) {
            $q->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        return response()->json($q->paginate(30));
    }

    /**
     * Lister les événements gérés ou créés par l'utilisateur actuel.
     *
     * @return JsonResponse Liste paginée des événements liés à l'utilisateur.
     */
    public function indexMine(Request $request)
    {
        $user = $request->user();
        $events = Event::query()
            ->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->with(['eventRequest', 'organizer'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($events);
    }

    /**
     * Lister les événements assignés à ou créés par n'importe quel organisateur (vue Administrateur).
     *
     * @return JsonResponse
     */
    public function indexOrganizerSpace(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $events = Event::query()
            ->where(function ($q) {
                $q->whereHas('organizer', fn ($q) => $q->where('role', User::ROLE_ORGANIZER))
                    ->orWhereHas('creator', fn ($q) => $q->where('role', User::ROLE_ORGANIZER));
            })
            ->with(['organizer', 'eventRequest', 'creator:id,name,role'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($events);
    }

    /**
     * Lister les événements spécifiquement assignés à l'administrateur actuel.
     *
     * @return JsonResponse
     */
    public function indexAssignedToMe(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        $events = Event::query()
            ->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->with(['eventRequest', 'organizer'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($events);
    }

    /**
     * Parcourir les événements publiés (vue publique).
     *
     * Renvoie uniquement les événements avec le statut STATUS_PUBLISHED qui ne sont pas terminés depuis plus d'un jour.
     * Prend en charge la recherche.
     *
     * @return JsonResponse Liste paginée des événements publiés.
     */
    public function browsePublished(EventIndexRequest $request)
    {
        $q = Event::query()
            ->where('status', Event::STATUS_PUBLISHED)
            ->where('start_at', '>=', now()->subDay())
            ->with(['organizer', 'eventRequest'])
            ->orderBy('start_at', 'asc');

        if ($search = $request->validated('q')) {
            $q->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        return response()->json($q->paginate(20));
    }

    /**
     * Obtenir les détails d'un seul événement.
     *
     * Les événements non publiés ne sont visibles que par leurs gestionnaires ou les administrateurs.
     *
     * @return JsonResponse Détails de l'événement avec les relations.
     */
    public function show(Request $request, Event $event)
    {
        if ($event->status !== Event::STATUS_PUBLISHED && ! $this->canManage($request, $event)) {
            abort(404);
        }

        return response()->json($event->load(['organizer', 'eventRequest', 'tasks', 'activities']));
    }

    /**
     * Créer un nouvel événement.
     *
     * @param  StoreEventRequest  $request  Données d'événement validées.
     * @return JsonResponse 201 Created.
     */
    public function store(StoreEventRequest $request)
    {
        $event = $this->events->create($request->user(), $request->validated());

        return response()->json($event, 201);
    }

    /**
     * Mettre à jour les détails de l'événement.
     *
     * @param  UpdateEventRequest  $request  Mises à jour d'événement validées.
     * @return JsonResponse Événement mis à jour.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event = $this->events->update($request->user(), $event, $request->validated());

        return response()->json($event);
    }

    /**
     * Mettre à jour la capacité de participants d'un événement.
     *
     * @param  UpdateEventCapacityRequest  $request  Capacité validée.
     * @return JsonResponse Événement mis à jour.
     */
    public function updateCapacity(UpdateEventCapacityRequest $request, Event $event)
    {
        $event = $this->events->updateCapacity($request->user(), $event, (int) $request->validated('capacity'));

        return response()->json($event);
    }

    /**
     * Assigner un organisateur spécifique pour gérer un événement.
     *
     * @param  AssignEventOrganizerRequest  $request  organizer_id validé.
     * @return JsonResponse Événement mis à jour.
     */
    public function assignOrganizer(AssignEventOrganizerRequest $request, Event $event)
    {
        $event = $this->events->assignOrganizer($event, $request->validated('organizer_id'));

        return response()->json($event);
    }

    /**
     * Supprimer un événement (Administrateur uniquement).
     *
     * @return JsonResponse 204 No Content.
     */
    public function destroy(Request $request, Event $event)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $event->delete();

        return response()->json(null, 204);
    }

    /**
     * Demander la publication d'un événement.
     *
     * Typiquement appelé par un organisateur lorsqu'il a terminé la planification.
     * Change le statut en PENDING_PUBLICATION.
     *
     * @return JsonResponse Événement mis à jour.
     */
    public function requestPublication(Request $request, Event $event)
    {
        $event = $this->events->requestPublication($request->user(), $event);

        return response()->json($event);
    }

    /**
     * Approuver la publication d'un événement (Administrateur uniquement).
     *
     * Change le statut en PUBLISHED, le rendant visible par tout le monde.
     *
     * @return JsonResponse Événement mis à jour.
     */
    public function approvePublication(Request $request, Event $event)
    {
        $event = $this->events->approvePublication($request->user(), $event);

        return response()->json($event);
    }

    /**
     * Aide interne pour vérifier si l'utilisateur actuel peut gérer un événement spécifique.
     *
     * @return bool True si Administrateur ou l'organisateur assigné.
     */
    private function canManage(Request $request, Event $event): bool
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return true;
        }

        return $event->isOrganizer($user);
    }
}
