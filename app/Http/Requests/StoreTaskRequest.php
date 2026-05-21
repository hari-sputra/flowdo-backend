<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'priority' => ['nullable', 'string', Rule::enum(TaskPriority::class)],
            'status' => ['nullable', 'string', Rule::enum(TaskStatus::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
