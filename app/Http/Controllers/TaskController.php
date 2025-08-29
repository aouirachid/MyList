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
    public function index(Request $request)
{
    $user = $request->attributes->get('jwt_user');
    $status = $request->query('status');
    $priority = $request->query('priority');

    // 1️⃣ Check if both are invalid
    if (!is_null($status) && !is_null($priority) &&
        !in_array((int)$status, [1, 2], true) &&
        !in_array((int)$priority, [1, 2, 3], true)
    ) {
        return response()->json([
            'message' => 'Invalid filter values provided',
        ], 422);
    }

    // 2️⃣ Check status only
    if (!is_null($status) && !in_array((int)$status, [1, 2], true)) {
        return response()->json([
            'message' => 'Invalid status value. Please use: 1 (pending), 2 (completed)',
        ], 422);
    }

    // 3️⃣ Check priority only
    if (!is_null($priority) && !in_array((int)$priority, [1, 2, 3], true)) {
        return response()->json([
            'message' => 'Invalid priority value. Please use: 1 (low), 2 (medium), 3 (high)',
        ], 422);
    }

    $sort = $request->query('sort');
    $direction = $request->query('direction', 'asc');
    $allowedSort = ['startDate', 'endDate', 'priority', 'created_at', 'title'];
    if (!is_null($sort) && !in_array($sort, $allowedSort, true)) {
        return response()->json([
            'message' => 'Invalid sort field. Please use: startDate, endDate',
        ], 422);
    }
    if (!in_array($direction, ['asc', 'desc'], true)) {
        return response()->json([
            'message' => 'Invalid sort direction',
        ], 422);
    }

    $startDate = $request->query('startDate');
$startFrom = $request->query('startDateFrom');
$startTo   = $request->query('startDateTo');

$isValidDate = function ($d) {
    if (!$d) return 'empty';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return 'format'; // wrong format
    }
    [$y, $m, $day] = explode('-', $d);
    if (!checkdate((int) $m, (int) $day, (int) $y)) {
        return 'invalid'; // impossible date
    }
    return true; // valid
};

// Validate startDate
$check = $isValidDate($startDate);
if ($startDate && $check !== true) {
    return response()->json([
        'message' => $check === 'format'
            ? 'Invalid date format. Please use YYYY-MM-DD format'
            : 'Invalid date provided',
    ], 422);
}

// Validate startFrom / startTo range
$checkFrom = $isValidDate($startFrom);
$checkTo   = $isValidDate($startTo);

if (($startFrom && $checkFrom !== true) || ($startTo && $checkTo !== true)) {
    return response()->json([
        'message' => ($checkFrom === 'format' || $checkTo === 'format')
            ? 'Invalid date format. Please use YYYY-MM-DD format'
            : 'Invalid date provided',
    ], 422);
}

// Validate range logic
if ($startFrom && $startTo && $startFrom > $startTo) {
    return response()->json([
        'message' => 'Start date cannot be after end date',
    ], 422);
}



    $query = Task::with(['tags', 'document'])->forUser($user->id);

    if (!is_null($status)) {
        $query->byStatus((int)$status);
    }
    if (!is_null($priority)) {
        $query->byPriority((int)$priority);
    }
    if ($startDate) {
        $query->byStartDate($startDate);
    }
    if ($startFrom && $startTo) {
        $query->byStartDateRange($startFrom, $startTo);
    }

    if ($sort) {
        $query->orderByField($sort, $direction);
    }

    $perPage = (int)$request->query('per_page', 15);
    $paginatedTasks = $query->paginate($perPage);
    
    return response()->json([
        'data' => $paginatedTasks->items(),
        'meta' => [
            'current_page' => $paginatedTasks->currentPage(),
            'per_page' => $paginatedTasks->perPage(),
            'total' => $paginatedTasks->total(),
            'last_page' => $paginatedTasks->lastPage(),
        ]
    ]);
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
