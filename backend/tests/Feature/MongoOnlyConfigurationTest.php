<?php

namespace Tests\Feature;

use Tests\TestCase;

class MongoOnlyConfigurationTest extends TestCase
{
    public function test_backend_exposes_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('services.mongodb.status', 'ok')
            ->assertJsonPath('services.redis.status', 'ok')
            ->assertJsonStructure([
                'status',
                'checked_at',
                'services' => [
                    'mongodb' => ['status'],
                    'redis' => ['status'],
                ],
            ]);
    }

    public function test_database_configuration_is_mongo_only(): void
    {
        $this->assertSame('mongodb', config('database.default'));
        $this->assertSame(['mongodb'], array_keys(config('database.connections')));
        $this->assertSame('redis', config('cache.default'));
        $this->assertSame('redis', config('queue.default'));
        $this->assertSame('redis', config('session.driver'));
    }
}
