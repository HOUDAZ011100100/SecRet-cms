<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\EventTasks\StoreEventTaskRequest;
use App\Http\Requests\EventTasks\UpdateEventTaskRequest;
use App\Models\Event;
use App\Models\EventTask;
use App\Services\Events\EventTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des tâches d'événement.
 *
 * Les tâches sont des éléments internes de la liste de choses à faire pour que les organisateurs gèrent la préparation de l'événement.
 * Ce contrôleur délègue toute la logique métier et l'autorisation à l'EventTaskService.
 */
class EventTaskController extends ApiController
{
    /**
     * @param  EventTaskService  $tasks  Service pour la gestion des tâches.
     */
    public function __construct(private readonly EventTaskService $tasks) {}

    /**
     * Lister toutes les tâches pour un événement spécifique.
     *
     * @param  Event  $event  Événement parent.
     * @return JsonResponse Liste des tâches.
     */
    public function index(Request $request, Event $event)
    {
        // Le service garantit que seuls les utilisateurs autorisés (Administrateur/Organisateur) peuvent voir les tâches
        return response()->json($this->tasks->list($this->actor($request), $event));
    }

    /**
     * Créer une nouvelle tâche pour un événement.
     *
     * @param  StoreEventTaskRequest  $request  Données de tâche validées.
     * @param  Event  $event  Événement parent.
     * @return JsonResponse 201 Created avec la nouvelle tâche.
     */
    public function store(StoreEventTaskRequest $request, Event $event)
    {
        $task = $this->tasks->create($this->actor($request), $event, $request->validated());

        return response()->json($task, 201);
    }

    /**
     * Mettre à jour une tâche existante.
     *
     * Utilisé pour changer le titre, le statut (terminé/en attente), etc.
     *
     * @param  UpdateEventTaskRequest  $request  Mises à jour de tâche validées.
     * @param  Event  $event  Événement parent.
     * @param  EventTask  $eventTask  Tâche à mettre à jour.
     * @return JsonResponse Tâche mise à jour.
     */
    public function update(UpdateEventTaskRequest $request, Event $event, EventTask $eventTask)
    {
        return response()->json($this->tasks->update($this->actor($request), $event, $eventTask, $request->validated()));
    }

    /**
     * Supprimer une tâche.
     *
     * @param  Event  $event  Événement parent.
     * @param  EventTask  $eventTask  Tâche à supprimer.
     * @return JsonResponse 204 No Content.
     */
    public function destroy(Request $request, Event $event, EventTask $eventTask)
    {
        $this->tasks->delete($this->actor($request), $event, $eventTask);

        return response()->json(null, 204);
    }
}
