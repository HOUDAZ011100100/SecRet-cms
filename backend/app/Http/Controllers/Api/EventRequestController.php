<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\EventRequests\EventRequestIndexRequest;
use App\Http\Requests\EventRequests\ReviewEventRequestRequest;
use App\Http\Requests\EventRequests\StoreClientEventRequest;
use App\Models\EventRequest;
use App\Services\EventRequestReviewService;
use App\Services\EventRequests\EventRequestSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des demandes d'événements des clients.
 *
 * Les clients utilisent ce contrôleur pour proposer de nouveaux événements. Les administrateurs l'utilisent pour
 * examiner (approuver/rejeter) ces demandes. Les demandes approuvées mènent généralement à la création d'un Événement.
 */
class EventRequestController extends ApiController
{
    /**
     * @param  EventRequestSubmissionService  $submissions  Service pour la soumission et la gestion des demandes.
     * @param  EventRequestReviewService  $reviews  Service pour la révision (approbation/rejet) des demandes.
     */
    public function __construct(
        private readonly EventRequestSubmissionService $submissions,
        private readonly EventRequestReviewService $reviews,
    ) {}

    /**
     * Soumettre une nouvelle demande d'événement.
     *
     * Typiquement appelé par un client.
     *
     * @param  StoreClientEventRequest  $request  Données de demande validées.
     * @return JsonResponse 201 Created.
     */
    public function store(StoreClientEventRequest $request)
    {
        $eventRequest = $this->submissions->submit($this->actor($request), $request->validated());

        return response()->json($eventRequest, 201);
    }

    /**
     * Supprimer une demande d'événement.
     *
     * @return JsonResponse 200 OK message.
     */
    public function destroy(Request $request, EventRequest $eventRequest)
    {
        $this->submissions->delete($this->actor($request), $eventRequest);

        return response()->json(['message' => 'Demande supprimée.']);
    }

    /**
     * Lister les demandes d'événements (vue Administrateur).
     *
     * @return JsonResponse Liste paginée des demandes.
     */
    public function index(EventRequestIndexRequest $request)
    {
        $query = EventRequest::query()
            ->with('event')
            ->orderBy('created_at', 'desc');

        // Filtrage optionnel par statut (pending, approved, rejected)
        if ($status = $this->validatedNullableString($request, 'status')) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Réviser une demande d'événement (Approuver ou Rejeter).
     *
     * Seuls les administrateurs doivent accéder à ce point de terminaison (généralement appliqué via un middleware).
     *
     * @param  ReviewEventRequestRequest  $request  Décision validée et motif optionnel.
     * @param  EventRequest  $eventRequest  La demande d'événement à réviser.
     * @return JsonResponse Demande mise à jour ou événement créé.
     */
    public function review(ReviewEventRequestRequest $request, EventRequest $eventRequest)
    {
        $data = $request->validated();

        // Gérer le rejet
        if ($data['decision'] === EventRequest::STATUS_REJECTED) {
            return response()->json($this->reviews->reject(
                $eventRequest,
                $this->actor($request),
                $this->validatedNullableString($request, 'rejection_reason'),
            ));
        }

        // Gérer l'approbation (cela déclenche généralement la création de l'événement)
        return response()->json($this->reviews->approve($eventRequest, $this->actor($request)));
    }
}
