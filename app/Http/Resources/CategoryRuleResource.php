<?php

namespace App\Http\Resources;

use App\Models\CategoryRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CategoryRule */
class CategoryRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field' => ['value' => $this->field->value, 'label' => $this->field->label()],
            'operator' => ['value' => $this->operator->value, 'label' => $this->operator->label()],
            'pattern' => $this->pattern,
            'priority' => $this->priority,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
            ],
        ];
    }
}
