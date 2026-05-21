<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('dueDate')) {
            $this->merge(['due_date' => $this->dueDate]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['sometimes', 'required', 'date'],
            'priority' => ['sometimes', 'required', 'string', Rule::enum(TaskPriority::class)],
            'status' => ['sometimes', 'required', 'string', Rule::enum(TaskStatus::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
