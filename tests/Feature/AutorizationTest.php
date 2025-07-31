<?php

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

it("access to an Api wiithout a JWT",function(){

    $response=$this->json('GET','/api/v1/tasks');
    $response->assertStatus(401)
    ->assertJson([
                 'message' => 'Token not provided',
             ]);
});

it("access with anvalid or expired JWT token",function(){
    $response=$this->json('GET','/api/v1/tasks',[],['Authorization'=>'
    Bearer invalid_token']);
    $response->assertStatus(401);
});

it("acces with valid JWT toke",function(){

    $user=User::factory()->create();

    $token=JWTAuth::fromUser($user);

    $response=$this->json('GET','/api/v1/tasks',[],['Authorization'=>'
    Bearer '.$token]);

    $response->assertStatus(200);
});