<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProjectRole;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is assigned to the project
        $projectId = $this->input('project_id');
        $userProject = auth()->user()->projects()
            ->where('projects.id', $projectId)
            ->first();

        if (!$userProject) {
            return false;
        }

        // Only owner and manager can create tasks
        $projectRole = $userProject->pivot->project_role;
        return in_array($projectRole, [ProjectRole::Owner->value, ProjectRole::Manager->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:todo,in_progress,done'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:users,id'],
        ];
    }
}
