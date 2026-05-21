<?php

namespace App\Http\Controllers\Api;

use App\Models\AppNotification;
use App\Services\Notifications\NotificationInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des notifications dans l'application pour l'utilisateur authentifié.
 */
class NotificationController extends ApiController
{
    public function __construct(private readonly NotificationInboxService $inbox) {}

    /**
     * Lister les notifications pour l'utilisateur authentifié.
     *
     * @return JsonResponse Notifications paginées avec le nombre de messages non lus.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->inbox->pageFor(
            $this->actor($request),
            $request->integer('page', 1),
            $request->boolean('unread_only'),
        ));
    }

    /**
     * Obtenir le nombre de notifications non lues pour l'utilisateur actuel.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->inbox->unreadCount($this->actor($request))]);
    }

    /**
     * Marquer une notification spécifique comme lue.
     *
     * @return JsonResponse La notification mise à jour.
     */
    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        // Autorisation : s'assurer que la notification appartient au demandeur
        abort_unless($notification->user_id === $this->actorId($request), 403);

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
    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $this->actorId($request))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Toutes les notifications ont été lues.']);
    }
}
