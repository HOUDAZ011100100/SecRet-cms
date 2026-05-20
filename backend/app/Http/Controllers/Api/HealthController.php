<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $health) {}

    public function __invoke(): JsonResponse
    {
        $report = $this->health->report();

        return response()->json($report, $this->health->allHealthy($report['services']) ? 200 : 503);
    }
}
