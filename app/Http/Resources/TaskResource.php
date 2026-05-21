<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value ?? $this->status,
            'dueDate' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
            'priority' => $this->priority->value ?? $this->priority,
            'completedAt' => $this->completed_at ? $this->completed_at->toISOString() : null,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
