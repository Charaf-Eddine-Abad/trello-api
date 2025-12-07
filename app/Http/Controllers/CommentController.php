<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/comments",
     *     summary="Get all comments for a task",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="task_id",
     *         in="query",
     *         description="Task ID to get comments for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comments retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="task_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="user", type="object")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized to view task comments"),
     *     @OA\Response(response=422, description="Validation error - task_id required")
     * )
     */
    public function index(Request $request)
    {
        // Validate task_id parameter
        $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        // Get the task
        $task = \App\Models\Task::find($request->task_id);

        // Check if user is assigned to the task's project
        if (!auth()->user()->projects()->where('projects.id', $task->project_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view comments for this task',
            ], 403);
        }

        // Get all comments for the task
        $comments = Comment::where('task_id', $request->task_id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Comments retrieved successfully',
            'data' => $comments,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/comments",
     *     summary="Add a comment to a task",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"task_id", "message"},
     *             @OA\Property(property="task_id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="This looks great!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment added successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="task_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized - must be in task's project"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCommentRequest $request)
    {
        // Create the comment
        $comment = Comment::create([
            'task_id' => $request->task_id,
            'user_id' => auth()->id(), // Auto-assign to authenticated user
            'message' => $request->message,
        ]);

        // Load the user relationship
        $comment->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment,
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/comments/{id}",
     *     summary="Delete a comment",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only comment author or project owner can delete"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     */
    public function destroy(string $id)
    {
        // Find the comment with task and project relationships
        $comment = Comment::with('task.project')->find($id);

        // Check if comment exists
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found',
            ], 404);
        }

        // Check authorization: comment author OR project owner
        $isAuthor = $comment->user_id === auth()->id();
        $isProjectOwner = $comment->task->project->created_by === auth()->id();

        if (!$isAuthor && !$isProjectOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Only the comment author or project owner can delete this comment',
            ], 403);
        }

        // Delete the comment
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }
}
