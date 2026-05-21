<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrations\ParticipantRegistrationIndexRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Services\Registrations\ParticipantRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contrôleur pour la gestion des inscriptions des participants aux événements.
 */
class RegistrationController extends Controller
{
    /**
     * @param  ParticipantRegistrationService  $registrations  Service pour les flux de travail d'inscription des participants.
     */
    public function __construct(private readonly ParticipantRegistrationService $registrations) {}

    /**
     * Inscrire l'utilisateur authentifié à un événement.
     *
     * @return JsonResponse 201 Created avec les détails de l'inscription.
     */
    public function store(Request $request, Event $event)
    {
        $registration = $this->registrations->register($this->actor($request), $event);

        return response()->json($registration, 201);
    }

    /**
     * Marquer une inscription comme payée.
     *
     * Dans une application réelle, cela serait déclenché par un rappel (callback) du fournisseur de paiement.
     *
     * @return JsonResponse Inscription mise à jour.
     */
    public function pay(Request $request, Registration $registration)
    {
        $registration = $this->registrations->pay($this->actor($request), $registration);

        return response()->json($registration);
    }

    /**
     * Annuler une inscription.
     *
     * @return JsonResponse 200 OK message.
     */
    public function destroy(Request $request, Registration $registration)
    {
        $this->registrations->cancel($this->actor($request), $registration);

        return response()->json(['message' => 'Inscription annulée.']);
    }

    /**
     * Récupérer les détails de l'inscription de l'utilisateur actuel pour un événement spécifique.
     *
     * Utilisé par l'interface utilisateur pour afficher le statut "Déjà inscrit" ou le lien de téléchargement du billet.
     *
     * @return JsonResponse
     */
    public function myRegistrationForEvent(Request $request, Event $event)
    {
        $registration = $this->registrations->registrationForEvent($this->actor($request), $event);

        return response()->json(['registration' => $registration]);
    }

    /**
     * Lister toutes les inscriptions de l'utilisateur authentifié.
     *
     * @return JsonResponse Liste des inscriptions.
     */
    public function myRegistrations(ParticipantRegistrationIndexRequest $request)
    {
        return response()->json($this->registrations->listForParticipant(
            $this->actor($request),
            $request->validated('payment_status'),
        ));
    }

    /**
     * Télécharger le billet pour une inscription.
     *
     * Renvoie un fichier de billet au format JSON.
     */
    public function ticket(Request $request, Registration $registration): StreamedResponse
    {
        $ticket = $this->registrations->ticketFor($this->actor($request), $registration);

        return response()->streamDownload(function () use ($ticket): void {
            // Encoder la charge utile du billet en JSON pour le téléchargement
            echo json_encode($ticket->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $ticket->filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Récupérer et valider l'utilisateur authentifié.
     */
    private function actor(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
