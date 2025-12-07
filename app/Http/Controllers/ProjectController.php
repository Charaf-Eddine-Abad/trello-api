<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/projects",
     *     summary="Get all projects for authenticated user",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Projects retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Projects retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Project Name"),
     *                 @OA\Property(property="description", type="string", example="Project description"),
     *                 @OA\Property(property="created_by", type="integer", example=1),
     *                 @OA\Property(property="tasks_count", type="integer", example=5)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        // Get all projects the authenticated user is assigned to
        $projects = auth()->user()->projects()
            ->with(['creator:id,name,email', 'users:id,name,email'])
            ->withCount('tasks')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Projects retrieved successfully',
            'data' => $projects,
        ]);
    }



    /**
     * @OA\Post(
     *     path="/projects",
     *     summary="Create a new project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="New Project"),
     *             @OA\Property(property="description", type="string", example="Project description"),
     *             @OA\Property(property="users", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Project created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Project created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreProjectRequest $request)
    {
        // 1. Create the Project
        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => auth()->id(),
        ]);

        // 2. Prepare user assignments with roles
        $userRoles = [];

        // Creator is always the owner
        $userRoles[auth()->id()] = ['project_role' => \App\Enums\ProjectRole::Owner->value];

        // Add other users with their specified roles (default to member)
        $users = $request->input('users', []);
        foreach ($users as $userId => $role) {
            // If users is an array of IDs (not associative), default to member
            if (is_numeric($userId)) {
                $userId = $role;
                $role = \App\Enums\ProjectRole::Member->value;
            }

            // Don't override creator's owner role
            if ($userId != auth()->id()) {
                $userRoles[$userId] = ['project_role' => $role];
            }
        }

        // Sync the users with their roles
        $project->users()->sync($userRoles);

        // Load relationships for response
        $project->load(['creator:id,name,email', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/projects/{id}",
     *     summary="Get a specific project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Project retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized to view this project"),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function show(string $id)
    {
        // Find the project
        $project = Project::with(['creator:id,name,email', 'users:id,name,email', 'tasks.assignedTo:id,name,email'])
            ->find($id);

        // Check if project exists
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        // Check if user is assigned to this project
        if (!auth()->user()->projects()->where('projects.id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this project',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project retrieved successfully',
            'data' => $project,
        ]);
    }



    /**
     * @OA\Put(
     *     path="/projects/{id}",
     *     summary="Update a project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Project Name"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="users", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Project updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not authorized to update this project"),
     *     @OA\Response(response=404, description="Project not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProjectRequest $request, string $id)
    {
        // Find the project
        $project = Project::find($id);

        // Check if project exists
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        // Update project details
        $project->update($request->validated());

        // Update assigned users with roles
        if ($request->has('users')) {
            $userRoles = [];

            // Ensure creator keeps owner role
            $userRoles[$project->created_by] = ['project_role' => \App\Enums\ProjectRole::Owner->value];

            // Add other users with their specified roles
            $users = $request->input('users', []);
            foreach ($users as $userId => $role) {
                // If users is an array of IDs (not associative), default to member
                if (is_numeric($userId)) {
                    $userId = $role;
                    $role = \App\Enums\ProjectRole::Member->value;
                }

                // Don't override creator's owner role
                if ($userId != $project->created_by) {
                    $userRoles[$userId] = ['project_role' => $role];
                }
            }

            // Sync the users with their roles
            $project->users()->sync($userRoles);
        }

        // Reload the project with relationships
        $project->load(['creator:id,name,email', 'users:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/projects/{id}",
     *     summary="Delete a project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Project deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only the project creator or admin can delete this project"),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function destroy(string $id)
    {
        // Find the project
        $project = Project::find($id);

        // Check if project exists
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        // Check if user is the project owner (creator) or system admin
        $isOwner = $project->created_by === auth()->id();
        $isAdmin = auth()->user()->role->value === 'admin';

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only the project owner or system admin can delete this project',
            ], 403);
        }

        // Delete the project (cascade will handle related records)
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
        ]);
    }
}
