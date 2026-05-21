<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Events\AssignEventOrganizerRequest;
use App\Http\Requests\Events\EventIndexRequest;
use App\Http\Requests\Events\UpdateEventCapacityRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventManagementService;
use App\Services\Events\EventListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEventController extends ApiController
{
    public function __construct(
        private readonly EventListingService $listings,
        private readonly EventManagementService $events,
    ) {}

    public function index(EventIndexRequest $request): JsonResponse
    {
        return response()->json(
            $this->listings->adminIndex($this->validatedNullableString($request, 'q')),
        );
    }

    public function organizerSpace(): JsonResponse
    {
        return response()->json($this->listings->organizerSpace());
    }

    public function assignedToMe(Request $request): JsonResponse
    {
        return response()->json($this->listings->assignedToAdmin($this->actor($request)));
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        return response()->json(
            $this->events->update($this->actor($request), $event, $request->validated()),
        );
    }

    public function updateCapacity(UpdateEventCapacityRequest $request, Event $event): JsonResponse
    {
        return response()->json(
            $this->events->updateCapacity($this->actor($request), $event, $this->validatedInt($request, 'capacity')),
        );
    }

    public function assignOrganizer(AssignEventOrganizerRequest $request, Event $event): JsonResponse
    {
        return response()->json(
            $this->events->assignOrganizer($event, $this->validatedString($request, 'organizer_id')),
        );
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(null, 204);
    }

    public function approvePublication(Request $request, Event $event): JsonResponse
    {
        return response()->json(
            $this->events->approvePublication($this->actor($request), $event),
        );
    }
}
