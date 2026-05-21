<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Stats\AdminStatsService;
use App\Services\Stats\ClientStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la récupération des statistiques pour les tableaux de bord.
 *
 * Prend en charge les statistiques globales pour les administrateurs et les statistiques personnelles pour les clients.
 */
class StatsController extends Controller
{
    /**
     * @param  AdminStatsService  $adminStats  Service pour les statistiques globales au niveau administrateur.
     * @param  ClientStatsService  $clientStats  Service pour les statistiques personnelles au niveau client.
     */
    public function __construct(
        private readonly AdminStatsService $adminStats,
        private readonly ClientStatsService $clientStats,
    ) {}

    /**
     * Obtenir les statistiques globales (vue Administrateur).
     *
     * Comprend des métriques telles que le nombre total d'utilisateurs, d'événements et le revenu global.
     *
     * @return JsonResponse Métriques globales.
     */
    public function admin(): JsonResponse
    {
        return response()->json($this->adminStats->payload());
    }

    /**
     * Obtenir les statistiques personnelles pour l'utilisateur authentifié (vue Client).
     *
     * Comprend des métriques telles que les événements suivis et l'argent dépensé.
     *
     * @return JsonResponse Métriques spécifiques à l'utilisateur.
     */
    public function client(Request $request): JsonResponse
    {
        return response()->json($this->clientStats->payloadFor($this->actor($request)));
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
