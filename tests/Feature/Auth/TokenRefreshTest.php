<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class); // Use RefreshDatabase trait for clean tests

it('returns new tokens and invalidates old refresh token', function () {
    // 1. Create user
    $user = User::factory()->create()->fresh();
    

    // 2. Generate tokens (simulate login)
    $originalAccessToken = JWTAuth::fromUser($user);
    $originalRefreshToken = JWTAuth::claims(['refresh' => true])->fromUser($user);

    // 3. Act as user and set refresh token
    $this->withHeader('Authorization', "Bearer $originalRefreshToken");

    // 4. Hit the refresh endpoint
    $response = $this->postJson('/api/v1/auth/refresh');

    // 5. Assert response status is 200
    $response->assertOk();

    // 6. Extract new tokens
    $newAccessToken = $response['access_token'];
    $newRefreshToken = $response['refresh_token'];

    // 7. Assert new tokens are not equal to the old ones
    expect($newAccessToken)->not->toBe($originalAccessToken);
    expect($newRefreshToken)->not->toBe($originalRefreshToken);

    // 8. Try using old refresh token again (simulate replay attack)
    $this->withHeader('Authorization', "Bearer $originalRefreshToken")
        ->postJson('/api/v1/auth/refresh')
        ->assertUnauthorized()
        ->assertJson(['error' => 'Could not refresh token']);
});
