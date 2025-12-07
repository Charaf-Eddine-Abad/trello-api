<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Task;
use App\Enums\ProjectRole;

class UpdateTaskStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = Task::find($this->route('id'));

        if (!$task) {
            return false;
        }

        // Check if user is assigned to the task's project
        $userProject = auth()->user()->projects()
            ->where('projects.id', $task->project_id)
            ->first();

        if (!$userProject) {
            return false;
        }

        $projectRole = $userProject->pivot->project_role;

        // Owner and Manager can update any task status
        $isOwnerOrManager = in_array($projectRole, [
            ProjectRole::Owner->value,
            ProjectRole::Manager->value
        ]);

        // Check if user is assigned to this task
        $isAssigned = $task->users()->where('users.id', auth()->id())->exists();

        // Allow if owner/manager OR assigned to the task
        return $isOwnerOrManager || $isAssigned;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:todo,in_progress,done'],
        ];
    }
}
