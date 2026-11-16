<?php

namespace App\Http\Resources;

use App\Models\BudgetAlert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BudgetAlert */
class BudgetAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->month,
            'level' => strtolower($this->level->name),
            'label' => $this->level->label(),
            'target' => $this->targetName(),
            'color' => $this->category?->color,
            'spent' => $this->spent,
            'limit' => $this->limit,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
