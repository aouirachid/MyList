<?php

use App\Http\Controllers\TaskController;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

it('as an authenticated user, I can list owned and collaborated tasks', function () {
    //create a user
    $user = User::factory()->create();
    //create another user
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
        'Authorization' => 'Bearer ' . $token,
    ]);
    $response->assertStatus(200);

    // Should include owned and collaborated tasks
    $response->assertJsonFragment([
        'id' => $ownedTask->id,
        'title' => 'Owned Task',
        'description' => 'Owned Description'
    ]);
    $response->assertJsonFragment([
        'id' => $collabTask->id,
        'title' => 'Collaborated Task',
        'description' => 'Collaborated Description'
    ]);

    // Should not include irrelevant task
    //irrelevantTask is a task the authenticated user should NOT see.
    // It’s owned by someone else
    $response->assertJsonMissing(['id' => $irrelevantTask->id]);
});

it('as an unauthenticated user, I cannot list tasks', function () {
    //try to list tasks
    $response = $this->getJson('/api/v1/tasks');
    //assert the response is unauthorized
    $response->assertStatus(401);
    //assert the response is json
    $response->assertJson([
        'message' => 'Token not provided',
    ]);
});

it('authenticated user can filter tasks by status or priority', function () {
    //create a user
    $user = User::factory()->create();
    //generate a token for the user
    $token = JWTAuth::fromUser($user);

    // Create tasks with different status and priority
    $pendingTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 1, // pending
        'priority' => 1, // low
    ]);
    //create a completed task
    $completedTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 2, // completed
        'priority' => 3, // high
    ]);

    // Filter by status = pending
    $response = $this->getJson('/api/v1/tasks?status=1', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $pendingTask->id]);
    $response->assertJsonMissing(['id' => $completedTask->id]);

    // Filter by priority = high
    $response = $this->getJson('/api/v1/tasks?priority=3', [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $completedTask->id]);
    $response->assertJsonMissing(['id' => $pendingTask->id]);
});

it('authenticated user can filter tasks by status AND priority', function () {
    //create user
    $user = User::factory()->create();
    //generate a token for the user
    $token = JWTAuth::fromUser($user);
    //create a pending task which is not completed
    $pendingTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 1, // pending
        'priority' => 1, // low
    ]);
    //create a completed task
    $completedTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 2, // completed
        'priority' => 3, // high
    ]);
    //create a completed task with low priority
    $completedLowPriorityTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 2, // completed
        'priority' => 1, // low
    ]);

    // Filter by status=2 AND priority=1 (should match only completedLowPriorityTask)
    $response = $this->getJson('/api/v1/tasks?status=2&priority=1', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $completedLowPriorityTask->id]);
    $response->assertJsonMissing(['id' => $pendingTask->id]);
    $response->assertJsonMissing(['id' => $completedTask->id]);
});

it('returns error message for invalid filter values', function () {
    //create a user
    $user = User::factory()->create();
    //generate a token for the user
    $token = JWTAuth::fromUser($user);
    
    // Create a task with valid values
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 1,
        'priority' => 1,
    ]);
    
    // Test invalid status value
    $response = $this->getJson('/api/v1/tasks?status=999', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid status value. Please use: 1 (pending), 2 (completed)',
    ]);
    
    // Test invalid priority value
    $response = $this->getJson('/api/v1/tasks?priority=999', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid priority value. Please use: 1 (low), 2 (medium), 3 (high)',
    ]);
    
    // Test invalid status AND priority
    $response = $this->getJson('/api/v1/tasks?status=999&priority=999', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    //assert the response is 422
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid filter values provided',
    ]);
});
//1.User wants to see all tasks but ordered by date
//2.User wants to see only tasks from a specific date/period
it('an authenticated user can sort by startDate or endDate', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'startDate' => '2025-01-01',
        'endDate' => '2025-01-02',
    ]);

    // sort by startDate
    $response = $this->getJson('/api/v1/tasks?sort=startDate', [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $task->id]); // ✅ works
    $response->assertJson([
        'data' => [
            ['id' => $task->id] // ✅ nested check
        ]
    ]);

    // sort by endDate
    $response = $this->getJson('/api/v1/tasks?sort=endDate', [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $task->id]);
    $response->assertJson([
        'data' => [
            ['id' => $task->id]
        ]
    ]);
});


it('authenticated user can filter tasks by date range', function () {
    //create a user
    $user = User::factory()->create();
    //generate a token for the user
    $token = JWTAuth::fromUser($user);
    
    // Create tasks with different dates
    $oldTask = Task::factory()->create([
        'user_id' => $user->id,
        'startDate' => '2025-01-01',
        'endDate' => '2025-01-02',
    ]);
    
    $recentTask = Task::factory()->create([
        'user_id' => $user->id,
        'startDate' => '2025-01-15',
        'endDate' => '2025-01-16',
    ]);
    
    $futureTask = Task::factory()->create([
        'user_id' => $user->id,
        'startDate' => '2025-02-01',
        'endDate' => '2025-02-02',
    ]);
    
    // Filter by specific start date
    $response = $this->getJson('/api/v1/tasks?startDate=2025-01-15', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $recentTask->id]);
    $response->assertJsonMissing(['id' => $oldTask->id]);
    $response->assertJsonMissing(['id' => $futureTask->id]);
    
    // Filter by date range
    $response = $this->getJson('/api/v1/tasks?startDateFrom=2025-01-01&startDateTo=2025-01-20', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $oldTask->id]);
    $response->assertJsonFragment(['id' => $recentTask->id]);
    $response->assertJsonMissing(['id' => $futureTask->id]);
});

it('returns error message for invalid date sorting values', function () {
    //create a user
    $user = User::factory()->create();
    //generate a token for the user
    $token = JWTAuth::fromUser($user);
    // Create a task
    $task = Task::factory()->create([
        'user_id' => $user->id,
    ]);
    // Test invalid sort field for dates
    $response = $this->getJson('/api/v1/tasks?sort=invalidDateField', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    //assert the response is 422
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid sort field. Please use: startDate, endDate',
    ]);
    // Test invalid date format in filter
    $response = $this->getJson('/api/v1/tasks?startDate=invalid-date', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid date format. Please use YYYY-MM-DD format',
    ]);
    // Test invalid date range (from date after to date)
    $response = $this->getJson('/api/v1/tasks?startDateFrom=2025-02-01&startDateTo=2025-01-01', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Start date cannot be after end date',
    ]);
    
    // Test future date that doesn't exist
    $response = $this->getJson('/api/v1/tasks?startDate=2025-13-45', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid date provided',
    ]);
});
it('returns paginated results', function () {
    //create user
    $user = User::factory()->create();
    // Generate a token for the user to authenticate API requests
    $token = JWTAuth::fromUser($user);
    // Create 15 tasks
    for ($i = 1; $i <= 15; $i++) {
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => "Task $i",
        ]);
    }
    // Test first page with 5 items
    $response = $this->getJson('/api/v1/tasks?page=1&per_page=5', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    // Assert that the response status is 200 (OK)
    $response->assertStatus(200);
    
    // Assert the JSON structure for paginated tasks
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'title', 'description', 'status', 'priority', 'startDate', 'endDate']
        ],
        'meta' => ['current_page','per_page','total','last_page']
    ]);
    // Assert that there are exactly 5 tasks in the data array
    $response->assertJsonCount(5, 'data');
    // Assert the pagination meta information is correct
    $response->assertJson([
        'meta' => [
            'current_page' => 1,
            'per_page' => 5,
            'total' => 15,
            'last_page' => 3
        ]
    ]);
});

it('returns task summary data with all required fields', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'status' => 1,
        'priority' => 2,
        'startDate' => '2025-01-01 10:00:00',
        'endDate' => '2025-01-02 18:00:00',
    ]);
    
    $response = $this->getJson('/api/v1/tasks', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'title', 
                'description',
                'status',
                'priority',
                'startDate',
                'endDate',
                'created_at',
                'updated_at'
            ]
        ]
    ]);
    
    $response->assertJsonFragment([
        'id' => $task->id,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'status' => 1,
        'priority' => 2,
    ]);
});

it('supports sort direction for both startDate and endDate', function () {
    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);
    
    $oldTask = Task::factory()->create([
        'user_id' => $user->id,
        'startDate' => '2025-01-01',
        'endDate' => '2025-01-05',  // Ends January 5th
        'title' => 'Old Task',
    ]);
    
    $newTask = Task::factory()->create([
        'user_id' => $user->id,
        'startDate' => '2025-01-15',
        'endDate' => '2025-01-20',  // Ends January 20th
        'title' => 'New Task',
    ]);
    
    // Test startDate ascending
    $response = $this->getJson('/api/v1/tasks?sort=startDate&direction=asc', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $data = $response->json('data');
    $this->assertEquals($oldTask->id, $data[0]['id']);
    $this->assertEquals($newTask->id, $data[1]['id']);
    
    // Test startDate descending
    $response = $this->getJson('/api/v1/tasks?sort=startDate&direction=desc', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $data = $response->json('data');
    $this->assertEquals($newTask->id, $data[0]['id']);
    $this->assertEquals($oldTask->id, $data[1]['id']);
    
    // Test endDate ascending
    $response = $this->getJson('/api/v1/tasks?sort=endDate&direction=asc', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $data = $response->json('data');
    $this->assertEquals($oldTask->id, $data[0]['id']);  // Ends January 5th first
    $this->assertEquals($newTask->id, $data[1]['id']);  // Ends January 20th second
    
    // Test endDate descending
    $response = $this->getJson('/api/v1/tasks?sort=endDate&direction=desc', [
        'Authorization' => 'Bearer ' . $token,
    ]);
    
    $response->assertStatus(200);
    $data = $response->json('data');
    $this->assertEquals($newTask->id, $data[0]['id']);  // Ends January 20th first
    $this->assertEquals($oldTask->id, $data[1]['id']);  // Ends January 5th second
});