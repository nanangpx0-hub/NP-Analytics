<?php

namespace Tests\Feature;

use Tests\TestCase;

class SyncKeyTest extends TestCase
{
    public function test_sync_endpoint_requires_configuration_outside_local(): void
    {
        config(['sync.api_key' => '']);

        $response = $this->getJson('/api/sync/pull');

        $response->assertStatus(503);
    }
}
