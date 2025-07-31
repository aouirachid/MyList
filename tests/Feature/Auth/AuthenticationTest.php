<?php

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;


test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertNoContent();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertNoContent();
});

it("user can loggout with valid JWT and acces denied after logout",function(){

     $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    // Logout
    $response = $this->withHeader('Authorization', "Bearer $token")
                     ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
             ->assertJson(['message' => 'Successfully logged out']);

    // Try to access a protected route with the same token
    $this->withHeader('Authorization', "Bearer $token")
         ->getJson('/api/v1/users')
         ->assertStatus(401);
});


