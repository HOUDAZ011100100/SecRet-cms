<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachRequestId
{
    private const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->requestId($request);
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    private function requestId(Request $request): string
    {
        $header = $request->headers->get(self::HEADER);

        if (is_string($header) && $this->isValid($header)) {
            return $header;
        }

        return (string) Str::uuid();
    }

    private function isValid(string $requestId): bool
    {
        return $requestId !== ''
            && strlen($requestId) <= 128
            && preg_match('/^[A-Za-z0-9._:-]+$/', $requestId) === 1;
    }
}
