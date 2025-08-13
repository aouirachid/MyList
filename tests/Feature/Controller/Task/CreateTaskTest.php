<?php

namespace Tests\Feature\Controller\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tymon\JWTAuth\Facades\JWTAuth;

test('An authenticated user successfully create a top-level task',function(){
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $task = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => null,
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks',
        $task,
        [
        'Authorization' => 'Bearer ' . $token
    ]);

    $response->assertStatus(201);
    // Assert the JSON response contains the success message and the task data
    $response->assertJson(
        fn(AssertableJson $json) =>
        $json->where('message', 'Task created successfully')
             ->where('data.title', $task['title'])
             ->where('data.description', $task['description'])
             ->where('data.user_id', $user->id)
             ->where('data.parent_task_id', null)
             ->etc()
    );
    $this->assertDatabaseHas('tasks', $task);
});