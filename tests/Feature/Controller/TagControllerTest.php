<?php

namespace Tests\Feature\Controller;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tymon\JWTAuth\Facades\JWTAuth;

test('can retrieve all tags', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $tag1 = Tag::factory()->create(['tagName' => 'Urgent']);
    $tag2 = Tag::factory()->create(['tagName' => 'Work']);
    $tag3 = Tag::factory()->create(['tagName' => 'Personal']);

    $response = $this->getJson('/api/v1/tags', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertStatus(200);
    $response->assertJson(
        fn (AssertableJson $json) => $json->where('message', 'Tags retrieved successfully')
            ->has('data', 3)
            ->where('data.0.tagName', 'Urgent')
            ->where('data.1.tagName', 'Work')
            ->where('data.2.tagName', 'Personal')
            ->etc()
    );
});

test('can create a new tag', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $tagData = [
        'tagName' => 'Important',
    ];

    $response = $this->postJson('/api/v1/tags', $tagData, [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertStatus(201);
    $response->assertJson(
        fn (AssertableJson $json) => $json->where('message', 'Tag created successfully')
            ->where('data.tagName', 'Important')
            ->etc()
    );

    $this->assertDatabaseHas('tags', $tagData);
});

test('tag creation validates required fields', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $response = $this->postJson('/api/v1/tags', [], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tagName']);
});
