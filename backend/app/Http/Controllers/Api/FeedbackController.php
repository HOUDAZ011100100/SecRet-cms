<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedbacks\StoreFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\User;
use App\Services\Feedbacks\FeedbackService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedbacks) {}

    public function index(Request $request, Event $event)
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return FeedbackResource::collection($this->feedbacks->listForEvent($user, $event));
    }

    public function store(StoreFeedbackRequest $request, Event $event)
    {
        $feedback = $this->feedbacks->submit($request->user(), $event, $request->validated());

        return FeedbackResource::make($feedback)
            ->additional(['message' => 'Votre avis a bien été envoyé. Il sera visible après validation par notre équipe.'])
            ->response()
            ->setStatusCode(201);
    }

    public function approve(Request $request, Feedback $feedback)
    {
        $result = $this->feedbacks->approve($request->user(), $feedback);

        return FeedbackResource::make($result->feedback)
            ->additional(['message' => $result->message]);
    }

    public function destroy(Request $request, Feedback $feedback)
    {
        $this->feedbacks->delete($request->user(), $feedback);

        return response()->json(null, 204);
    }
}
