<?php

namespace Tests\Feature\Controller\Task;

use App\Models\Document;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('an authenticated user can update their own task', function () {

    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Task',
        'description' => 'Test Description',
    ]);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'title' => 'Updated Task',
        'description' => 'Updated Description',
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Task updated successfully',
    ]);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Task',
        'description' => 'Updated Description',
    ]);
});

test('changing the parentTaskId to move a task under a different parent', function () {
    $parentTask1 = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Parent Task 1',
        'description' => 'Parent Task 1 Description',
    ]);
    $parentTask2 = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Parent Task 2',
        'description' => 'Parent Task 2 Description',
    ]);
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Task',
        'description' => 'Task Description',
        'parentTaskId' => $parentTask1->id,
    ]);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'parentTaskId' => $parentTask2->id,
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Task updated successfully',
    ]);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'parentTaskId' => $parentTask2->id,
    ]);
});

test('that invalid data returns 422 with validation errors', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Task',
        'description' => 'Task Description',
    ]);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'title' => '',
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Task',
        'description' => 'Task Description',
    ]);
});

test('updating the collaborators list', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Task',
        'description' => 'Task Description',
    ]);
    $collaborator1 = User::factory()->create();
    $collaborator2 = User::factory()->create();
    $collaborator3 = User::factory()->create();
    $collaborator4 = User::factory()->create();
    $task->users()->attach([$collaborator1->id, $collaborator2->id]);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'users' => [$collaborator3->id, $collaborator4->id],
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Task updated successfully',
    ]);
    $this->assertDatabaseHas('collaborators', [
        'user_id' => $collaborator3->id,
        'task_id' => $task->id,
    ]);
    $this->assertDatabaseHas('collaborators', [
        'user_id' => $collaborator4->id,
        'task_id' => $task->id,
    ]);

    $this->assertDatabaseMissing('collaborators', [
        'user_id' => $collaborator1->id,
        'task_id' => $task->id,
    ]);
    $this->assertDatabaseMissing('collaborators', [
        'user_id' => $collaborator2->id,
        'task_id' => $task->id,
    ]);
});

test('updating the tag list', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Task',
        'description' => 'Task Description',
    ]);
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    $task->tags()->attach([$tag1->id]);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'tags' => [$tag2->id],
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Task updated successfully',
    ]);
    $this->assertDatabaseHas('task__tags', [
        'tag_id' => $tag2->id,
        'task_id' => $task->id,
    ]);
    $this->assertDatabaseMissing('task__tags', [
        'tag_id' => $tag1->id,
        'task_id' => $task->id,
    ]);
});

test('updating the document', function () {
    $document = Document::factory()->create();
    $updatedDocument = Document::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Task',
        'description' => 'Task Description',
        'document_id' => $document->id,
    ]);
    $token = JWTAuth::fromUser($this->user);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'document_id' => $updatedDocument->id,
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Task updated successfully',
    ]);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'document_id' => $updatedDocument->id,
    ]);
});

test('unauthorized user cannot update task', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Task',
        'description' => 'Task Description',
    ]);
    $unauthorizedUser = User::factory()->create();
    $token = JWTAuth::fromUser($unauthorizedUser);
    $response = $this->putJson('/api/v1/tasks/'.$task->id, [
        'title' => 'Updated Task',
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);
    $response->assertStatus(403);
    $response->assertJson([
        'message' => 'You are not authorized to update this task',
    ]);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Task',
        'description' => 'Task Description',
    ]);
});
