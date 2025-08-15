<?php
namespace Tests\Feature\Controller\Task;

use App\Models\Task;
use App\Models\User;
use App\Models\Tag;
use App\Models\Document;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tymon\JWTAuth\Contracts\Providers\JWT;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('An authenticated user successfully create a top-level task',function(){
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
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
        'tag_id' => $tag->id,
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
            ->where('data.tag_id', $tag->id)
            ->etc()
    );
    $this->assertDatabaseHas('tasks', $task);
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
        'tag_id' => $tag->id,
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
        'tag_id' => $tag->id,
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
            ->where('data.tag_id', $tag->id)
            ->etc()
    );
    $this->assertDatabaseHas('tasks', $secondTask);
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
        'tag_id' => $tag->id,
        'status' => 1,
    ];

    $response = $this->postJson(
        '/api/v1/tasks',
        $task,
        [
            'Authorization' => 'Bearer ' . $token
        ]
    );

    $response->assertStatus(201);
    $response->assertJson(
        fn(AssertableJson $json) =>
        $json->where('message', 'Task created successfully')
            ->where('data.title', $task['title'])
            ->where('data.document_id', $document->id)
            ->where('data.tag_id', $tag->id)
            ->etc()
    );

    $this->assertDatabaseHas('tasks', $task);
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
        'tag_id' => $tag->id,
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['endDate']);
    $this->assertDatabaseMissing('tasks', $task);
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
        'tag_id' => $tag->id,
        'status' => 1,
    ];

    $childTaskResponse = $this->postJson('/api/v1/tasks', $childTask, [
        'Authorization' => 'Bearer ' . $token
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
        'tag_id' => 999,
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tag_id']);
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
        'tag_id' => $tag->id,
        'status' => 1,
    ];

    $response = $this->postJson('/api/v1/tasks', $task, [
        'Authorization' => 'Bearer ' . $token
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['document_id']);
    $this->assertDatabaseCount('tasks', 0);
});