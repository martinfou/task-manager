<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    public function test_manifest_returns_json_and_icons(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/manifest+json; charset=UTF-8');

        $data = $response->json();
        $this->assertSame(config('app.name'), $data['name']);
        $this->assertSame('standalone', $data['display']);
        $this->assertSame('/icons/icon-192.png', $data['icons'][0]['src']);
        $this->assertSame('/icons/icon-512.png', $data['icons'][1]['src']);
    }

    public function test_service_worker_is_served(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/javascript; charset=UTF-8');
        $this->assertStringContainsString('gt-assets', file_get_contents(public_path('sw.js')) ?: '');
    }
}
