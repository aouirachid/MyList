<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\CreateTaskRequest;
use App\Models\Document;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        } elseif (isset($validated['documentId'])) {
            $documentId = $validated['documentId'];
        }

        $task = Task::create([
            'user_id' => $validated['user_id'],
            'document_id' => $documentId,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'startDate' => $validated['startDate'],
            'endDate' => $validated['endDate'],
            'priority' => $validated['priority'],
            'tag_id' => $validated['tag_id'],
            'parentTaskId' => $validated['parentTaskId'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
