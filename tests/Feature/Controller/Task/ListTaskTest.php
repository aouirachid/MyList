<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

it('as an authenticated user, I can list owned and collaborated tasks', function () {
    // create a user
    $user = User::factory()->create();
    // create another user
    $otherUser = User::factory()->create();
    // Owned task
    $ownedTask = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Owned Task',
        'description' => 'Owned Description',
    ]);
    $ownedTask->users()->attach($user->id);

    // Task created by someone else but current user is a collaborator
    $collabTask = Task::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Collaborated Task',
        'description' => 'Collaborated Description',
    ]);
    $collabTask->users()->attach([$user->id, $otherUser->id]);

    // Irrelevant task (neither owned nor collaborated)
    $irrelevantTask = Task::factory()->create();
    $irrelevantTask->users()->attach($otherUser->id);

    $token = JWTAuth::fromUser($user);

    $response = $this->getJson('/api/v1/tasks', [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);

    // Should include owned and collaborated tasks
    $response->assertJsonFragment([
        'id' => $ownedTask->id,
        'title' => 'Owned Task',
        'description' => 'Owned Description',
    ]);
    $response->assertJsonFragment([
        'id' => $collabTask->id,
        'title' => 'Collaborated Task',
        'description' => 'Collaborated Description',
    ]);

    // Should not include irrelevant task
    // irrelevantTask is a task the authenticated user should NOT see.
    // It’s owned by someone else
    $response->assertJsonMissing(['id' => $irrelevantTask->id]);
});

it('as an unauthenticated user, I cannot list tasks', function () {
    // try to list tasks
    $response = $this->getJson('/api/v1/tasks');
    // assert the response is unauthorized
    $response->assertStatus(401);
    // assert the response is json
    $response->assertJson([
        'message' => 'Token not provided',
    ]);
});
