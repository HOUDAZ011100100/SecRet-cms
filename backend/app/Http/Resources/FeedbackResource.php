<?php

namespace App\Http\Resources;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $feedback = $this->resource;
        if (! $feedback instanceof Feedback) {
            return [];
        }

        $user = $feedback->relationLoaded('user') ? $feedback->getRelation('user') : null;
        $user = $user instanceof User ? $user : null;

        return [
            'id' => $feedback->getKey(),
            'event_id' => $feedback->getAttribute('event_id'),
            'rating' => (int) $feedback->getAttribute('rating'),
            'comment' => $feedback->getAttribute('comment'),
            'status' => $feedback->getAttribute('status'),
            'created_at' => $feedback->getAttribute('created_at'),
            'user' => $user ? [
                'id' => $user->getKey(),
                'name' => $user->getAttribute('name'),
            ] : null,
        ];
    }
}
