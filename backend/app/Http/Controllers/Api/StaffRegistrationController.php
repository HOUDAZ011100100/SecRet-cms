<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Registrations\StaffRegistrationIndexRequest;
use App\Models\Registration;
use App\Services\Registrations\StaffRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des inscriptions des participants du point de vue du personnel (Organisateur/Administrateur).
 *
 * Ce contrôleur permet au personnel de visualiser, rechercher et gérer les inscriptions pour les événements qu'ils gèrent.
 */
class StaffRegistrationController extends ApiController
{
    /**
     * @param  StaffRegistrationService  $registrations  Service pour la gestion des inscriptions au niveau du personnel.
     */
    public function __construct(private readonly StaffRegistrationService $registrations) {}

    /**
     * Lister les événements gérés par l'organisateur actuel ayant au moins une inscription.
     *
     * @return JsonResponse
     */
    public function eventsForOrganizer(Request $request)
    {
        return response()->json($this->registrations->eventsForOrganizer($this->actor($request)));
    }

    /**
     * Lister tous les événements ayant au moins une inscription (vue Administrateur).
     *
     * @return JsonResponse
     */
    public function eventsForAdmin(Request $request)
    {
        return response()->json($this->registrations->eventsForAdmin($this->actor($request)));
    }

    /**
     * Lister les inscriptions pour un événement géré par l'organisateur.
     *
     * @param  StaffRegistrationIndexRequest  $request  Filtres validés (event_id, statut, recherche).
     * @return JsonResponse Liste paginée des inscriptions.
     */
    public function indexForOrganizer(StaffRegistrationIndexRequest $request)
    {
        return response()->json($this->registrations->listForOrganizer(
            $this->actor($request),
            $request->validated(),
        ));
    }

    /**
     * Lister les inscriptions pour n'importe quel événement (vue Administrateur).
     *
     * @param  StaffRegistrationIndexRequest  $request  Filtres validés (event_id, statut, recherche).
     * @return JsonResponse Liste paginée des inscriptions.
     */
    public function indexForAdmin(StaffRegistrationIndexRequest $request)
    {
        return response()->json($this->registrations->listForAdmin(
            $this->actor($request),
            $request->validated(),
        ));
    }

    /**
     * Supprimer/Annuler une inscription en tant qu'organisateur.
     *
     * @return JsonResponse 200 OK message.
     */
    public function destroyForOrganizer(Request $request, Registration $registration)
    {
        $this->registrations->deleteForOrganizer($this->actor($request), $registration);

        return response()->json(['message' => 'Inscription supprimée.']);
    }

    /**
     * Supprimer/Annuler une inscription en tant qu'administrateur.
     *
     * @return JsonResponse 200 OK message.
     */
    public function destroyForAdmin(Request $request, Registration $registration)
    {
        $this->registrations->deleteForAdmin($this->actor($request), $registration);

        return response()->json(['message' => 'Inscription supprimée.']);
    }
}
