<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventCapacityRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventManagementService;
use App\Services\Events\EventListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerEventController extends ApiController
{
    public function __construct(
        private readonly EventListingService $listings,
        private readonly EventManagementService $events,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->listings->managedBy($this->actor($request)));
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        return response()->json(
            $this->events->create($this->actor($request), $request->validated()),
            201,
        );
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

    public function requestPublication(Request $request, Event $event): JsonResponse
    {
        return response()->json(
            $this->events->requestPublication($this->actor($request), $event),
        );
    }
}
