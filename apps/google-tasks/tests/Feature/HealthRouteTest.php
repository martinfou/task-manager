<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthRouteTest extends TestCase
{
    public function test_health_returns_json_ok(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $response->assertJson([
            'status' => 'ok',
        ]);
        $response->assertJsonStructure(['status', 'app']);
    }
}
