<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Task;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get the task
        $task = Task::find($this->input('task_id'));

        if (!$task) {
            return false;
        }

        // Check if user is assigned to the task's project
        return auth()->user()->projects()
            ->where('projects.id', $task->project_id)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'exists:tasks,id'],
            'message' => ['required', 'string', 'min:1'],
        ];
    }
}
