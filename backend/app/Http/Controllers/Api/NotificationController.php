<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des notifications dans l'application pour l'utilisateur authentifié.
 */
class NotificationController extends Controller
{
    /**
     * Lister les notifications pour l'utilisateur authentifié.
     *
     * @return JsonResponse Notifications paginées avec le nombre de messages non lus.
     */
    public function index(Request $request)
    {
        $query = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filtrage optionnel pour afficher uniquement les notifications non lues
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $paginated = $query->paginate(30);
        $userId = $request->user()->id;

        // Calculer le nombre total de messages non lus pour le badge de l'interface utilisateur
        $unreadCount = AppNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => $paginated->items(),
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Obtenir le nombre de notifications non lues pour l'utilisateur actuel.
     *
     * @return JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $count = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Marquer une notification spécifique comme lue.
     *
     * @return JsonResponse La notification mise à jour.
     */
    public function markRead(Request $request, AppNotification $notification)
    {
        // Autorisation : s'assurer que la notification appartient au demandeur
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json($notification->fresh());
    }

    /**
     * Marquer toutes les notifications de l'utilisateur actuel comme lues.
     *
     * @return JsonResponse 200 OK message.
     */
    public function markAllRead(Request $request)
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Toutes les notifications ont été lues.']);
    }
}
