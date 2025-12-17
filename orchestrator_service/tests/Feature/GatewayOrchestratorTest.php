<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class GatewayOrchestratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'http://localhost:8000/api/users' => Http::response([
                'status' => true,
                'data' => []
            ], 200),

            'http://localhost:8001/api/v1/products' => Http::response([
                'success' => true,
                'data' => []
            ], 200),
        ]);
    }

    /** @test */
    public function gateway_can_call_user_service()
    {
        $response = $this->get('/api/users');

        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'success',
            'service' => 'user',
        ]);
    }

    /** @test */
    public function correlation_id_is_propagated()
    {
        $cid = 'uas-test-123';

        $response = $this->withHeaders([
            'X-Correlation-ID' => $cid,
        ])->get('/api/users');

        $response->assertHeader('X-Correlation-ID', $cid);
    }

    /** @test */
    public function authorization_token_is_forwarded()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
        ])->get('/api/users');

        $response->assertStatus(200);
    }
}
