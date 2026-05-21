<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Events\EventIndexRequest;
use App\Models\Event;
use App\Services\Events\EventListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEventController extends ApiController
{
    public function __construct(private readonly EventListingService $events) {}

    public function browse(EventIndexRequest $request): JsonResponse
    {
        return response()->json(
            $this->events->published($this->validatedNullableString($request, 'q')),
        );
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $actor = $this->actor($request);
        if ($event->status !== Event::STATUS_PUBLISHED && ! $event->isOrganizer($actor)) {
            abort(404);
        }

        return response()->json($event->load(['organizer', 'eventRequest', 'tasks', 'activities']));
    }
}
