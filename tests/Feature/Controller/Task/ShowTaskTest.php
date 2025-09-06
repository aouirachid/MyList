<?php

namespace Tests\Feature\Controller\Task;

use App\Models\Document;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->collaborator1 = User::factory()->create();
    $this->collaborator2 = User::factory()->create();
    $this->tag = Tag::factory()->create();
    $this->document = Document::factory()->create();
});

test('a user successfully retrieving their own task', function () {
    // Create task specific to this test
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Parent Task',
        'description' => 'Parent Task Description',
        'document_id' => $this->document->id,
    ]);
    $task->tags()->attach($this->tag->id);
    $task->users()->attach($this->collaborator1->id);
    $task->users()->attach($this->collaborator2->id);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->getJson('/api/v1/tasks/'.$task->id, [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson(
        fn (AssertableJson $json) => $json->where('data.id', $task->id)
            ->where('data.title', $task->title)
            ->where('data.description', $task->description)
            ->where('data.document_id', $this->document->id)
            ->where('data.parentTaskId', null)
            ->where('data.status', 1)
            ->has('data.users', 2)
            ->where('data.users.0.id', $this->collaborator1->id)
            ->where('data.users.1.id', $this->collaborator2->id)
            ->has('data.tags', 1)
            ->where('data.tags.0.id', $this->tag->id)
            ->etc()
    );
});

test('a collaborator successfully retrieving task', function () {
    // Create task specific to this test
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Parent Task',
        'description' => 'Parent Task Description',
        'document_id' => $this->document->id,
    ]);
    $task->tags()->attach($this->tag->id);
    $task->users()->attach($this->collaborator1->id);
    $task->users()->attach($this->collaborator2->id);
    $token = JWTAuth::fromUser($this->collaborator1);
    $response = $this->getJson('/api/v1/tasks/'.$task->id, [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson(
        fn (AssertableJson $json) => $json->where('data.id', $task->id)
            ->where('data.title', $task->title)
            ->where('data.description', $task->description)
            ->where('data.document_id', $this->document->id)
            ->where('data.parentTaskId', null)
            ->where('data.status', 1)
            ->has('data.users', 2)
            ->where('data.users.0.id', $this->collaborator1->id)
            ->where('data.users.1.id', $this->collaborator2->id)
            ->has('data.tags', 1)
            ->where('data.tags.0.id', $this->tag->id)
            ->etc()
    );
});

test('unauthorized user cannot retrieve task', function () {
    // Create task here too for consistency
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Parent Task',
        'description' => 'Parent Task Description',
        'document_id' => $this->document->id,
    ]);
    $task->tags()->attach($this->tag->id);
    $task->users()->attach([$this->collaborator1->id, $this->collaborator2->id]);

    $unauthorizedUser = User::factory()->create();
    $token = JWTAuth::fromUser($unauthorizedUser);

    $response = $this->getJson('/api/v1/tasks/'.$task->id, [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertForbidden()
        ->assertJson(['message' => 'You are not authorized to access this task']);
});

test('eagerly loads the task with its relationships', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Parent Task',
        'description' => 'Parent Task Description',
        'document_id' => $this->document->id,
    ]);
    $task->tags()->attach($this->tag->id);
    $task->users()->attach($this->collaborator1->id);
    $task->users()->attach($this->collaborator2->id);
    $token = JWTAuth::fromUser($this->user);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->getJson('/api/v1/tasks/'.$task->id, [
        'Authorization' => 'Bearer '.$token,
    ]);

    $queries = DB::getQueryLog();

    expect(count($queries))->toBeLessThanOrEqual(5)
        ->and($response->status())->toBe(200);
});
