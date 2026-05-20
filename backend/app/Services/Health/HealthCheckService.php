<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use MongoDB\Laravel\Connection as MongoConnection;
use RuntimeException;
use Throwable;

class HealthCheckService
{
    /**
     * @return array{
     *     status: 'ok'|'degraded',
     *     checked_at: string,
     *     services: array<string, array{status: 'ok'|'down', error?: string}>
     * }
     */
    public function report(): array
    {
        $services = [
            'mongodb' => $this->checkMongo(),
            'redis' => $this->checkRedis(),
        ];

        return [
            'status' => $this->allHealthy($services) ? 'ok' : 'degraded',
            'checked_at' => now()->toIso8601String(),
            'services' => $services,
        ];
    }

    /**
     * @param  array<string, array{status: 'ok'|'down', error?: string}>  $services
     */
    public function allHealthy(array $services): bool
    {
        foreach ($services as $service) {
            if ($service['status'] !== 'ok') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{status: 'ok'|'down', error?: string}
     */
    private function checkMongo(): array
    {
        try {
            $connection = DB::connection('mongodb');

            if (! $connection instanceof MongoConnection) {
                throw new RuntimeException('MongoDB connection is not using the MongoDB driver.');
            }

            $connection->getDatabase()->command(['ping' => 1])->toArray();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return $this->down($exception);
        }
    }

    /**
     * @return array{status: 'ok'|'down', error?: string}
     */
    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return $this->down($exception);
        }
    }

    /**
     * @return array{status: 'down', error: string}
     */
    private function down(Throwable $exception): array
    {
        return [
            'status' => 'down',
            'error' => $exception->getMessage(),
        ];
    }
}
