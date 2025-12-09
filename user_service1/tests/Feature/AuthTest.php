<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuthTest extends TestCase
{
    use  DatabaseMigrations;

    /** @test */
    public function user_can_register_and_get_token()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }
}
