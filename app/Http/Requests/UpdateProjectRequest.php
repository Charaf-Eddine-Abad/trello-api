<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProjectRole;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is assigned to the project with owner or manager role
        $project = $this->route('project');
        $userProject = auth()->user()->projects()
            ->where('projects.id', $project)
            ->first();

        if (!$userProject) {
            return false;
        }

        // Only owner and manager can update project
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // users is an array of user IDs to assign to the project
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'], // Ensure every ID in the array exists
        ];
    }
}
