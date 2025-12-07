<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\Project;
use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tasks",
     *     summary="Get all tasks for authenticated user's projects",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="project_id",
     *         in="query",
     *         description="Filter by project ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"todo", "in_progress", "done"})
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by priority",
     *         required=false,
     *         @OA\Schema(type="string", enum={"low", "medium", "high"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tasks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tasks retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="project_id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="priority", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        // Get projects user is assigned to
        $projectIds = auth()->user()->projects()->pluck('projects.id');

        // Query tasks from these projects
        $query = Task::whereIn('project_id', $projectIds)
            ->with(['project:id,name', 'users:id,name,email'])
            ->withCount('comments');

        // Apply filters
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully',
            'data' => $tasks,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/tasks",
     *     summary="Create a new task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"project_id", "title"},
     *             @OA\Property(property="project_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Implement feature X"),
     *             @OA\Property(property="description", type="string", example="Detailed description"),
     *             @OA\Property(property="status", type="string", enum={"todo", "in_progress", "done"}, example="todo"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="assigned_users", type="array", @OA\Items(type="integer"), example={2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized - only owner/manager can create tasks"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTaskRequest $request)
    {
        // Create the task
        $task = Task::create([
            'project_id' => $request->project_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? TaskStatus::Todo->value,
            'priority' => $request->priority ?? TaskPriority::Low->value,
            'due_date' => $request->due_date,
        ]);

        // Assign users to the task
        if ($request->has('assigned_users')) {
            $task->users()->sync($request->assigned_users);

            // Send notifications to assigned users
            foreach ($task->users as $user) {
                $user->notify(new \App\Notifications\TaskAssignedNotification($task, auth()->user()));
            }
        }

        // Load relationships
        $task->load(['project:id,name', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/tasks/{id}",
     *     summary="Get task details",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized to view this task"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show(string $id)
    {
        // Find the task
        $task = Task::with(['project:id,name', 'users:id,name,email', 'comments.user:id,name'])
            ->find($id);

        // Check if task exists
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        // Check if user is assigned to the task's project
        if (!auth()->user()->projects()->where('projects.id', $task->project_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this task',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task retrieved successfully',
            'data' => $task,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/tasks/{id}",
     *     summary="Update task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string", enum={"todo", "in_progress", "done"}),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="assigned_users", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized to update this task"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateTaskRequest $request, string $id)
    {
        // Find the task
        $task = Task::find($id);

        // Check if task exists
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        // Get user's project role
        $userProject = auth()->user()->projects()
            ->where('projects.id', $task->project_id)
            ->first();

        $projectRole = $userProject->pivot->project_role;
        $isOwnerOrManager = in_array($projectRole, [ProjectRole::Owner->value, ProjectRole::Manager->value]);

        // Update task details
        $task->update($request->validated());

        // Only owner/manager can reassign users
        if ($request->has('assigned_users') && $isOwnerOrManager) {
            $task->users()->sync($request->assigned_users);
        }

        // Reload relationships
        $task->load(['project:id,name', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/tasks/{id}",
     *     summary="Delete task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only project owner can delete tasks"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function destroy(string $id)
    {
        // Find the task
        $task = Task::with('project')->find($id);

        // Check if task exists
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        // Only project owner can delete tasks
        if ($task->project->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the project owner can delete tasks',
            ], 403);
        }

        // Delete the task (cascade will handle related records)
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/tasks/{id}/status",
     *     summary="Update task status",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"todo", "in_progress", "done"}, example="in_progress")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task status updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized - must be owner/manager or assigned to task"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateStatus(\App\Http\Requests\UpdateTaskStatusRequest $request, string $id)
    {
        // Find the task
        $task = Task::with('users')->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        // Store old status
        $oldStatus = $task->status->value;

        // Update status
        $task->update(['status' => $request->status]);

        // Send notifications to all assigned users EXCEPT the one who made the change
        foreach ($task->users as $user) {
            if ($user->id !== auth()->id()) {
                $user->notify(new \App\Notifications\TaskStatusChangedNotification(
                    $task,
                    $oldStatus,
                    $request->status,
                    auth()->user()
                ));
            }
        }

        // Reload relationships
        $task->load(['project:id,name', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
            'data' => $task,
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/tasks/{id}/priority",
     *     summary="Update task priority",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"priority"},
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task priority updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task priority updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized - only owner/manager can update priority"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePriority(\App\Http\Requests\UpdateTaskPriorityRequest $request, string $id)
    {
        // Find the task
        $task = Task::with('users')->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        // Store old priority
        $oldPriority = $task->priority->value;

        // Update priority
        $task->update(['priority' => $request->priority]);

        // Send notifications to all assigned users EXCEPT the one who made the change
        foreach ($task->users as $user) {
            if ($user->id !== auth()->id()) {
                $user->notify(new \App\Notifications\TaskPriorityChangedNotification(
                    $task,
                    $oldPriority,
                    $request->priority,
                    auth()->user()
                ));
            }
        }

        // Reload relationships
        $task->load(['project:id,name', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task priority updated successfully',
            'data' => $task,
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/tasks/{id}/assignees",
     *     summary="Update task assignees",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"assigned_users"},
     *             @OA\Property(property="assigned_users", type="array", @OA\Items(type="integer"), example={2, 3, 4})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task assignees updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task assignees updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized - only owner/manager can reassign users"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateAssignees(\App\Http\Requests\UpdateTaskAssigneesRequest $request, string $id)
    {
        // Find the task
        $task = Task::with('users')->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        // Get old assignees
        $oldUserIds = $task->users->pluck('id')->toArray();

        // Sync assigned users
        $task->users()->sync($request->assigned_users);

        // Get newly assigned users (not previously assigned)
        $newUserIds = array_diff($request->assigned_users, $oldUserIds);

        // Notify only newly assigned users
        foreach ($newUserIds as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->notify(new \App\Notifications\TaskAssignedNotification($task, auth()->user()));
            }
        }

        // Reload relationships
        $task->load(['project:id,name', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task assignees updated successfully',
            'data' => $task,
        ]);
    }
}
