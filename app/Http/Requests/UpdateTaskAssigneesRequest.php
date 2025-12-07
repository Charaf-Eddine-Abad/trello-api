<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Task;
use App\Enums\ProjectRole;

class UpdateTaskAssigneesRequest extends FormRequest
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

        // Only Owner and Manager can reassign users
        return in_array($projectRole, [
            ProjectRole::Owner->value,
            ProjectRole::Manager->value
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_users' => ['required', 'array'],
            'assigned_users.*' => ['exists:users,id'],
        ];
    }
}
