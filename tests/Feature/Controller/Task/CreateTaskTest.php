<?php

namespace Tests\Feature\Controller\Task;

use App\Models\Document;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tymon\JWTAuth\Facades\JWTAuth;

test('An authenticated user successfully create a top-level task', function () {
    $user = User::factory()->create();
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
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
        'tags' => [$tag1->id, $tag2->id],
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks',
        $task,
        [
            'Authorization' => 'Bearer '.$token,
        ]);

    $response->assertStatus(201);
    // Assert the JSON response contains the success message and the task data
    $response->assertJson(
        fn (AssertableJson $json) => $json->where('message', 'Task created successfully')
            ->where('data.title', $task['title'])
            ->where('data.description', $task['description'])
            ->where('data.user_id', $user->id)
            ->where('data.parentTaskId', null)
            ->has('data.tags', 2)
            ->where('data.tags.0.id', $tag1->id)
            ->where('data.tags.1.id', $tag2->id)
            ->etc()
    );

    // Verify task was created without tag_id field
    $taskWithoutTags = array_diff_key($task, array_flip(['tags']));
    $this->assertDatabaseHas('tasks', $taskWithoutTags);

    // Verify tags are attached in pivot table
    $this->assertDatabaseHas('task__tags', ['task_id' => $response->json('data.id'), 'tag_id' => $tag1->id]);
    $this->assertDatabaseHas('task__tags', ['task_id' => $response->json('data.id'), 'tag_id' => $tag2->id]);
});

test('An authenticated user successfully create a sub task', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
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
        'tags' => [$tag->id],
        'status' => 1,
    ];

    $firstTaskResponse = $this->postJson(
        '/api/v1/tasks',
        $firstTask,
        [
            'Authorization' => 'Bearer '.$token,
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
        'tags' => [$tag->id],
        'status' => 1,
    ];

    $childResponse = $this->postJson(
        '/api/v1/tasks',
        $secondTask,
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );
    $childResponse->assertStatus(201);
    $childResponse->assertJson(
        fn (AssertableJson $json) => $json->where('message', 'Task created successfully')
            ->where('data.title', $secondTask['title'])
            ->where('data.description', $secondTask['description'])
            ->where('data.user_id', $user->id)
            ->where('data.parentTaskId', $firstTaskId)
            ->has('data.tags', 1)
            ->where('data.tags.0.id', $tag->id)
            ->etc()
    );

    $taskWithoutTags = array_diff_key($secondTask, array_flip(['tags']));
    $this->assertDatabaseHas('tasks', $taskWithoutTags);
    $this->assertDatabaseHas('task__tags', ['task_id' => $childResponse->json('data.id'), 'tag_id' => $tag->id]);
});

test('An authenticated user successfully create a task with document upload', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $document = Document::factory()->create();
    $token = JWTAuth::fromUser($user);

    $task = [
        'user_id' => $user->id,
        'document_id' => $document->id,
        'title' => 'Test Task with Document',
        'description' => 'Test Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => null,
        'tags' => [$tag->id],
        'status' => 1,
    ];

    $response = $this->postJson(
        '/api/v1/tasks',
        $task,
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );

    $response->assertStatus(201);
    $response->assertJson(
        fn (AssertableJson $json) => $json->where('message', 'Task created successfully')
            ->where('data.title', $task['title'])
            ->where('data.document_id', $document->id)
            ->has('data.tags', 1)
            ->where('data.tags.0.id', $tag->id)
            ->etc()
    );

    $taskWithoutTags = array_diff_key($task, array_flip(['tags']));
    $this->assertDatabaseHas('tasks', $taskWithoutTags);
    $this->assertDatabaseHas('task__tags', ['task_id' => $response->json('data.id'), 'tag_id' => $tag->id]);
});

it('handle validation errors when creating a task', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $token = JWTAuth::fromUser($user);

    $task = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => null,
        'tags' => [$tag->id],
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['endDate']);
    $taskWithoutTags = array_diff_key($task, array_flip(['tags']));
    $this->assertDatabaseMissing('tasks', $taskWithoutTags);
});

it("can't create a task with a parent task that doesn't exist", function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $token = JWTAuth::fromUser($user);

    $childTask = [
        'user_id' => $user->id,
        'document_id' => null,
        'title' => 'Child Task',
        'description' => 'Child Task Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => 999,
        'tags' => [$tag->id],
        'status' => 1,
    ];

    $childTaskResponse = $this->postJson('/api/v1/tasks', $childTask, [
        'Authorization' => 'Bearer '.$token,
    ]);
    $childTaskResponse->assertStatus(422);
    $childTaskResponse->assertJsonValidationErrors(['parentTaskId']);
    $this->assertDatabaseCount('tasks', 0);
});

it("can't create a task with a tag that doesn't exist", function () {
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
        'tags' => [999],
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tags.0']);
    $this->assertDatabaseCount('tasks', 0);
});

it("can't create a task with a document that doesn't exist", function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    $token = JWTAuth::fromUser($user);

    $task = [
        'user_id' => $user->id,
        'document_id' => 999,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'startDate' => Carbon::now()->format('Y-m-d H:i:s'),
        'endDate' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'priority' => 1,
        'parentTaskId' => null,
        'tags' => [$tag->id],
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['document_id']);
    $this->assertDatabaseCount('tasks', 0);
});
