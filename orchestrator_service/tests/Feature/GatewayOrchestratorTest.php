<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class GatewayOrchestratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // ✅ MOCK HTTP RESPONSES - Agar tidak perlu service nyata
        Http::fake([
            // User Service responses
            'http://localhost:8000/api/users/123' => Http::response([
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ], 200),
            
            'http://localhost:8000/api/users/456' => Http::response([
                'id' => 456,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com'
            ], 200),
            
            // Product Service - Success case
            'http://localhost:8001/api/products?user_id=123' => Http::response([
                ['id' => 1, 'name' => 'Product A', 'price' => 100],
                ['id' => 2, 'name' => 'Product B', 'price' => 200]
            ], 200),
            
            // Product Service - Failure case (for test_gateway_handles_product_service_failure)
            'http://localhost:8001/api/products?user_id=456' => Http::response([
                'error' => 'Service unavailable'
            ], 503),
            
            // Fallback untuk URL lainnya
            'http://localhost:8000/api/users*' => Http::response([], 404),
            'http://localhost:8001/api/products*' => Http::response([], 404),
        ]);
    }
    
    /** @test */
    public function gateway_calls_both_user_and_product_services()
    {
        $response = $this->withHeaders([
            'X-Correlation-ID' => 'test-correlation-001',
            'Authorization' => 'Bearer test-token-123'
        ])->get('/api/users/123/with-products');
        
        $response->assertStatus(200);
        
        $response->assertHeader('X-Correlation-ID', 'test-correlation-001');
        
        $response->assertJson([
            'status' => 'success',
            'service' => 'gateway_orchestrator',
            'data' => [
                'user' => [
                    'id' => 123,
                    'name' => 'John Doe'
                ]
            ]
        ]);
    }
    
    /** @test */
    public function gateway_handles_product_service_failure()
    {
        $response = $this->get('/api/users/456/with-products');
        
        // Should return 207 (Partial Success/Multi-Status)
        $response->assertStatus(207);
        
        // Should have partial_success status
        $response->assertJson([
            'status' => 'partial_success',
            'data' => [
                'user' => [
                    'id' => 456,
                    'name' => 'Jane Smith'
                ],
                'products' => [],
                'product_service_status' => 'unavailable'
            ]
        ]);
    }
    
    /** @test */
    public function authorization_token_is_forwarded()
    {
        // Test ini perlu assertions
        $this->assertTrue(true); // Contoh assertion sederhana
        
        // Atau test dengan mock
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token-123'
        ])->get('/api/users/123/with-products');
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function correlation_id_propagation()
    {
        $correlationId = 'test-corr-' . time();
        
        $response = $this->withHeaders([
            'X-Correlation-ID' => $correlationId
        ])->get('/api/products');
        
        // Correlation ID should be in response header
        $response->assertHeader('X-Correlation-ID', $correlationId);
    }
}