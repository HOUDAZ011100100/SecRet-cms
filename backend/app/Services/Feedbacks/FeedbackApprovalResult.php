<?php

namespace App\Services\Feedbacks;

use App\Models\Feedback;

readonly class FeedbackApprovalResult
{
    public function __construct(
        public Feedback $feedback,
        public string $message,
    ) {}
}
