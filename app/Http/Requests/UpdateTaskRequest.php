<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProjectRole;
use App\Models\Task;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = Task::find($this->route('task'));

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

        // Owner and Manager can update any task
        if (in_array($projectRole, [ProjectRole::Owner->value, ProjectRole::Manager->value])) {
            return true;
        }

        // Regular members can only update if they're assigned to the task
        return $task->users()->where('users.id', auth()->id())->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:todo,in_progress,done'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:users,id'],
        ];
    }
}
