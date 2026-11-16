<?php

namespace App\Http\Requests;

use App\Domain\Categorization\RuleField;
use App\Domain\Categorization\RuleOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRuleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'field' => ['required', Rule::enum(RuleField::class)],
            'operator' => ['required', Rule::enum(RuleOperator::class)],
            'pattern' => ['required', 'string', 'max:120'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }
}
