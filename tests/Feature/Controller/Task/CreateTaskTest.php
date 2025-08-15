<?php
namespace Tests\Feature\Controller\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tymon\JWTAuth\Contracts\Providers\JWT;
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
            ->where('data.parentTaskId', null)
            ->etc()
    );
    $this->assertDatabaseHas('tasks', $task);
});

test('An authenticated user successfully create a sub task', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    $firstTask = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Super Task',
        'description' => 'Super Task Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => null,
        'status' => 1,
    ];

    $firstTaskResponse = $this->postJson(
        '/api/v1/tasks',
        $firstTask,
        [
            'Authorization' => 'Bearer ' . $token
        ]
    );
    $firstTaskResponse->assertStatus(201);
    $firstTaskId = $firstTaskResponse->json('data.id');

    $secondTask = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Sub Task',
        'description' => 'Sub Task Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => $firstTaskId,
        'status' => 1,
    ];


    $childResponse = $this->postJson(
        '/api/v1/tasks',
        $secondTask,
        [
            'Authorization' => 'Bearer ' . $token
        ]
    );
    $childResponse->assertStatus(201);
    $childResponse->assertJson(
        fn(AssertableJson $json) =>
        $json->where('message', 'Task created successfully')
            ->where('data.title', $secondTask['title'])
            ->where('data.description', $secondTask['description'])
            ->where('data.user_id', $user->id)
            ->where('data.parentTaskId', $firstTaskId)
            ->etc()
    );
    $this->assertDatabaseHas('tasks', $secondTask);
});

it('handle validation errors when creating a task', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $task = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => null,
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['message']);
    $response->assertDatabaseMissing('tasks', $task);
});

it("can't create a task with a parent task that doesn't exist", function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $childTask = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Child Task',
        'description' => 'Child Task Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => 3,
        'status' => 1,
    ];

    $childTaskResponse = $this->postJson('/api/v1/tasks', $childTask, [
        'Authorization' => 'Bearer ' . $token
    ]);
    $childTaskResponse->assertStatus(403);
    $this->assertDatabaseCount('tasks', 0);
});