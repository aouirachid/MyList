<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\CreateTaskRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the authenticated user from the request
        $user = $request->attributes->get('jwt_user');
        // Check if the user is not authenticated
        if (! $user) {
            return response()->json([
                'message' => 'Token not provided',
            ], 401);
        }
        // Fetch tasks (owned and collaborated) for the authenticated user
        $tasks = Task::getAllTasks();

        // Return the tasks
        return response()->json([
            'message' => 'Tasks fetched successfully',
            'data' => $tasks,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $documentId = null;
        if ($request->hasFile('document')) {
            $documentResponse = app(DocumentController::class)->store($request);
            $documentData = json_decode($documentResponse->getContent(), true);
            $documentId = $documentData['data']['id'];
        } elseif (isset($validated['document_id'])) {
            $documentId = $validated['document_id'];
        }

        $task = Task::create([
            'user_id' => $validated['user_id'],
            'document_id' => $documentId,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'startDate' => $validated['startDate'],
            'endDate' => $validated['endDate'],
            'priority' => $validated['priority'],
            'parentTaskId' => $validated['parentTaskId'] ?? null,
            'status' => $validated['status'],
        ]);

        // Attach tags to the task
        if (isset($validated['tags']) && is_array($validated['tags'])) {
            $task->tags()->attach($validated['tags']);
        }

        // Load the tags relationship for the response
        $task->load('tags');

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // Get the authenticated user from the request (already loaded by middleware)
        $user = $request->attributes->get('jwt_user');
        if (! $user) {
            return response()->json([
                'message' => 'Token not provided',
            ], 401);
        }

        $userId = $user->id;
        $task = Task::with(['users', 'tags', 'document'])->findOrFail($id);

        // Check authorization using the already loaded users collection
        if ($task->user_id != $userId && ! $task->users->pluck('id')->contains($userId)) {
            return response()->json([
                'message' => 'You are not authorized to access this task',
            ], 403);
        }

        return response()->json([
            'message' => 'Task fetched successfully',
            'data' => $task,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
