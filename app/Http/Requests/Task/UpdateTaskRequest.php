<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|exists:users,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'startDate' => 'sometimes|date',
            'endDate' => 'sometimes|date',
            'priority' => 'sometimes|integer',
            'parentTaskId' => 'sometimes|nullable|exists:tasks,id',
            'document_id' => 'sometimes|nullable|exists:documents,id',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'exists:tags,id',
            'status' => 'sometimes|integer',
        ];
    }
}
