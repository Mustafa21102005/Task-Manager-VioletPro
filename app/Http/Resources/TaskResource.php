<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'is_completed' => $this->is_completed,
            'recurrence' => $this->recurrence?->value,
            'recurrence_label' => $this->recurrenceRule()?->label(),
            'recurrence_interval' => $this->recurrence_interval,
            'recurrence_unit' => $this->recurrence_unit?->value,
            'recurrence_days' => $this->recurrence_days,
        ];
    }
}
