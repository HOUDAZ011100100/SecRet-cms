<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\EventActivities\StoreEventActivityRequest;
use App\Http\Requests\EventActivities\UpdateEventActivityRequest;
use App\Models\Event;
use App\Models\EventActivity;
use App\Services\Events\EventActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des activités d'événement (programme/calendrier).
 *
 * Les activités sont des éléments spécifiques dans le programme d'un événement (ex : ateliers, discours).
 * Ce contrôleur délègue la logique métier et les vérifications d'autorisation à l'EventActivityService.
 */
class EventActivityController extends ApiController
{
    /**
     * @param  EventActivityService  $activities  Service pour la gestion des activités.
     */
    public function __construct(private readonly EventActivityService $activities) {}

    /**
     * Lister toutes les activités pour un événement spécifique.
     *
     * @param  Event  $event  L'événement parent de ces activités.
     * @return JsonResponse Liste des activités.
     */
    public function index(Request $request, Event $event)
    {
        // Le service gère l'autorisation : généralement seuls les organisateurs ou les administrateurs peuvent voir tous les détails
        return response()->json($this->activities->list($this->actor($request), $event));
    }

    /**
     * Créer une nouvelle activité pour un événement.
     *
     * @param  StoreEventActivityRequest  $request  Données d'activité validées.
     * @param  Event  $event  L'événement auquel l'activité sera ajoutée.
     * @return JsonResponse 201 Created avec la nouvelle activité.
     */
    public function store(StoreEventActivityRequest $request, Event $event)
    {
        $activity = $this->activities->create($this->actor($request), $event, $request->validated());

        return response()->json($activity, 201);
    }

    /**
     * Mettre à jour une activité existante.
     *
     * @param  UpdateEventActivityRequest  $request  Mises à jour d'activité validées.
     * @param  Event  $event  L'événement parent.
     * @param  EventActivity  $eventActivity  L'activité à mettre à jour.
     * @return JsonResponse L'activité mise à jour.
     */
    public function update(UpdateEventActivityRequest $request, Event $event, EventActivity $eventActivity)
    {
        return response()->json($this->activities->update($this->actor($request), $event, $eventActivity, $request->validated()));
    }

    /**
     * Supprimer une activité d'un événement.
     *
     * @param  Event  $event  L'événement parent.
     * @param  EventActivity  $eventActivity  L'activité à supprimer.
     * @return JsonResponse 204 No Content.
     */
    public function destroy(Request $request, Event $event, EventActivity $eventActivity)
    {
        $this->activities->delete($this->actor($request), $event, $eventActivity);

        return response()->json(null, 204);
    }
}
